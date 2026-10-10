<?php
namespace Tests\Unit;

use App\Services\InvestmentFinancialChartsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class InvestmentFinancialChartsTest extends TestCase
{
    private function project(int $id, ?int $companyId = null): object
    {
        return (object) ['id'=>$id, 'company_id'=>$companyId, 'company'=>null, 'company_name'=>'Company '.$id, 'title'=>'Project '.$id];
    }
    private function statement(int $id, array $values = []): object
    {
        return (object) array_replace(['project_id'=>$id, 'year'=>1405, 'month'=>12, 'net_sales'=>null, 'net_profit'=>null,
            'gross_profit'=>null, 'total_current_assets'=>null, 'total_current_liabilities'=>null, 'cash_and_equivalents'=>null,
            'total_assets'=>null, 'total_liabilities'=>null, 'total_equity'=>null], $values);
    }
    private function report(array $projects, array $statements, array $payments = [], array $filters = []): array
    {
        return (new InvestmentFinancialChartsService)->build(new Request(array_replace(['period_type'=>'annual'], $filters)), collect($projects), collect($statements), collect($payments));
    }
    public function test_ratios_use_sums_and_keep_large_amounts_exact(): void
    {
        $result = $this->report([$this->project(1),$this->project(2)], [
            $this->statement(1,['net_sales'=>'100','net_profit'=>'50', 'total_current_assets'=>'9007199254740993','total_current_liabilities'=>'1']),
            $this->statement(2,['net_sales'=>'900','net_profit'=>'90', 'total_current_assets'=>'1','total_current_liabilities'=>'1'])]);
        $row = $result['periods'][0];
        $this->assertSame('14.0000', $row['net_margin']);
        $this->assertSame('9007199254740992', $row['working_capital']);
        $this->assertSame('1000', $row['sales']);
    }
    public function test_missing_amounts_and_nonpositive_denominators_are_not_zero(): void
    {
        $result = $this->report([$this->project(1)], [$this->statement(1,['net_sales'=>'0','net_profit'=>'-20','total_current_assets'=>'40','total_current_liabilities'=>'0','total_assets'=>'-1','total_liabilities'=>'5'])]);
        $row=$result['periods'][0];
        $this->assertSame('0',$row['sales']);
        $this->assertSame('-20',$row['profit']);
        $this->assertNull($row['net_margin']);
        $this->assertNull($row['current_ratio']);
        $this->assertNull($row['debt_assets']);
        $this->assertNull($result['quality'][0]['negative_equity']);
    }
    public function test_incomplete_cohort_is_a_gap_and_latest_comparison_uses_one_period(): void
    {
        $result=$this->report([$this->project(1),$this->project(2)], [
            $this->statement(1,['year'=>1404,'net_profit'=>'5']),
            $this->statement(2,['year'=>1405,'net_profit'=>'-10'])]);
        $this->assertNull($result['periods'][1]['profit']);
        $this->assertSame(1,$result['periods'][1]['coverage']['net_profit']);
        $this->assertSame([null,'-10'],$result['charts'][6]['series'][0]['values']);
    }
    public function test_duplicate_company_statements_are_flagged_not_summed(): void
    {
        $result=$this->report([$this->project(1,8),$this->project(2,8)], [$this->statement(1,['net_sales'=>'50']),$this->statement(2,['net_sales'=>'50'])]);
        $this->assertSame(1,$result['expected']);
        $this->assertSame(1,$result['periods'][0]['conflicts']);
        $this->assertNull($result['periods'][0]['sales']);
    }
    public function test_quarterly_basis_is_explicit_and_missing_quarters_are_visible(): void
    {
        $statements=[$this->statement(1,['month'=>3,'net_sales'=>'50']),$this->statement(1,['month'=>9,'net_sales'=>'80'])];
        $result=$this->report([$this->project(1)],$statements,[],['period_type'=>'quarterly']);
        $this->assertFalse($result['incomeAllowed']);
        $this->assertSame(['1405/03','1405/06','1405/09'],array_column($result['periods'],'period'));
        $confirmed=$this->report([$this->project(1)],$statements,[],['period_type'=>'quarterly','income_basis'=>'ytd']);
        $this->assertSame(['50',null,'80'],array_column($confirmed['periods'],'sales'));
    }
    public function test_balance_check_uses_assets_minus_liabilities_and_equity(): void
    {
        $result=$this->report([$this->project(1)],[$this->statement(1,['total_assets'=>'100','total_liabilities'=>'140','total_equity'=>'-40'])]);
        $this->assertSame('0',$result['quality'][0]['balance_difference']);
        $this->assertTrue($result['quality'][0]['negative_equity']);
        $this->assertSame('140.0000',$result['periods'][0]['debt_assets']);
    }
    public function test_payment_chart_groups_company_dossiers_and_preserves_other_companies(): void
    {
        $projects=[];$payments=[];
        for($id=1;$id<=12;$id++){$projects[]=$this->project($id);$payments[]=(object)['project_id'=>$id,'amount'=>'100'];}
        $result=$this->report($projects,[],$payments,['from_date'=>'1405/01/01']);
        $this->assertCount(11,$result['charts'][5]['labels']);
        $this->assertSame('200',$result['charts'][5]['series'][0]['values'][10]);
        $this->assertSame('سایر شرکت‌ها',$result['charts'][5]['labels'][10]);
    }
    public function test_legacy_income_is_not_assumed_and_invalid_period_is_excluded(): void
    {
        $result=$this->report([$this->project(1)],[$this->statement(1,['net_sales'=>'100'])],[],['period_type'=>'legacy','income_basis'=>'standalone']);
        $this->assertNull($result['periods'][0]['sales']);
        $result=$this->report([$this->project(1)],[$this->statement(1,['month'=>5])],[],['period_type'=>'quarterly']);
        $this->assertSame(1,$result['invalidPeriods']);
        $this->assertSame([],$result['periods']);
    }
}
