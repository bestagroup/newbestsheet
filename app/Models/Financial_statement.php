<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class Financial_statement extends Model
{
    use HasFactory;

    private const MONETARY_FIELDS = [
        'net_sales',
        'operating_revenue',
        'cogs_goods',
        'cogs_services',
        'gross_profit',
        'selling_general_admin_expense',
        'operating_loss',
        'financial_expense',
        'other_income',
        'non_operating_net',
        'profit_before_tax',
        'income_tax_expense',
        'net_profit',
        'tangible_fixed_assets',
        'intangible_assets',
        'other_assets',
        'total_non_current_assets',
        'prepayments',
        'inventory',
        'trade_receivables',
        'other_receivables',
        'cash_and_equivalents',
        'total_current_assets',
        'total_assets',
        'capital',
        'capital_in_progress',
        'legal_reserve',
        'retained_earnings',
        'total_equity',
        'long_term_rnd_payable',
        'long_term_loans',
        'employee_benefit_reserve',
        'total_non_current_liabilities',
        'trade_payables',
        'tax_payable',
        'short_term_loans',
        'advances_received',
        'total_current_liabilities',
        'total_liabilities',
        'total_equity_and_liabilities',
    ];

    private const FIELD_GROUPS = [
        'صورت سود و زیان' => [
            'net_sales' => 'فروش خالص',
            'operating_revenue' => 'درآمدهای عملیاتی',
            'cogs_goods' => 'بهای تمام‌شده کالای فروش‌رفته',
            'cogs_services' => 'بهای تمام‌شده خدمات',
            'gross_profit' => 'سود ناخالص',
            'selling_general_admin_expense' => 'هزینه‌های فروش، اداری و عمومی',
            'operating_loss' => 'سود / زیان عملیاتی',
            'financial_expense' => 'هزینه‌های مالی',
            'other_income' => 'سایر درآمدها',
            'non_operating_net' => 'خالص درآمدها و هزینه‌های غیرعملیاتی',
            'profit_before_tax' => 'سود / زیان قبل از مالیات',
            'income_tax_expense' => 'هزینه مالیات بر درآمد',
            'net_profit' => 'سود / زیان خالص',
        ],
        'دارایی‌ها' => [
            'tangible_fixed_assets' => 'دارایی‌های ثابت مشهود',
            'intangible_assets' => 'دارایی‌های نامشهود',
            'other_assets' => 'سایر دارایی‌ها',
            'total_non_current_assets' => 'جمع دارایی‌های غیرجاری',
            'prepayments' => 'پیش‌پرداخت‌ها',
            'inventory' => 'موجودی مواد و کالا',
            'trade_receivables' => 'دریافتنی‌های تجاری',
            'other_receivables' => 'سایر دریافتنی‌ها',
            'cash_and_equivalents' => 'موجودی نقد و معادل نقد',
            'total_current_assets' => 'جمع دارایی‌های جاری',
            'total_assets' => 'جمع دارایی‌ها',
        ],
        'حقوق مالکانه و بدهی‌ها' => [
            'capital' => 'سرمایه',
            'capital_in_progress' => 'سرمایه در جریان',
            'legal_reserve' => 'اندوخته قانونی',
            'retained_earnings' => 'سود / زیان انباشته',
            'total_equity' => 'جمع حقوق مالکانه',
            'long_term_rnd_payable' => 'پرداختنی بلندمدت تحقیق و توسعه',
            'long_term_loans' => 'تسهیلات مالی بلندمدت',
            'employee_benefit_reserve' => 'ذخیره مزایای پایان خدمت',
            'total_non_current_liabilities' => 'جمع بدهی‌های غیرجاری',
            'trade_payables' => 'پرداختنی‌های تجاری',
            'tax_payable' => 'مالیات پرداختنی',
            'short_term_loans' => 'تسهیلات مالی کوتاه‌مدت',
            'advances_received' => 'پیش‌دریافت‌ها',
            'total_current_liabilities' => 'جمع بدهی‌های جاری',
            'total_liabilities' => 'جمع بدهی‌ها',
            'total_equity_and_liabilities' => 'جمع حقوق مالکانه و بدهی‌ها',
        ],
    ];

    protected $fillable = [
        'project_id',
        'year',
        'month',
        'period_type',
        ...self::MONETARY_FIELDS,
    ];

    protected $casts = [
        'project_id' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
    ];

    public static function monetaryFields(): array
    {
        return self::MONETARY_FIELDS;
    }

    public static function fieldGroups(): array
    {
        return self::FIELD_GROUPS;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeFilter(Builder $query, Request $request): Builder
    {
        $projectId = $request->input('project_id', $request->input('company_id'));
        [$fromYear, $fromMonth] = $this->periodParts($request->input('from_date'));
        [$toYear, $toMonth] = $this->periodParts($request->input('to_date'));

        return $query
            ->where('period_type', in_array($request->input('period_type'), ['annual', 'quarterly', 'legacy'], true) ? $request->input('period_type') : 'legacy')
            ->when($projectId, fn (Builder $builder) => $builder->where('project_id', (int) $projectId))
            ->when($fromYear, function (Builder $builder) use ($fromYear, $fromMonth): void {
                $builder->where(function (Builder $period) use ($fromYear, $fromMonth): void {
                    $period->where('year', '>', $fromYear)
                        ->orWhere(function (Builder $sameYear) use ($fromYear, $fromMonth): void {
                            $sameYear->where('year', $fromYear)
                                ->where('month', '>=', $fromMonth ?: 1);
                        });
                });
            })
            ->when($toYear, function (Builder $builder) use ($toYear, $toMonth): void {
                $builder->where(function (Builder $period) use ($toYear, $toMonth): void {
                    $period->where('year', '<', $toYear)
                        ->orWhere(function (Builder $sameYear) use ($toYear, $toMonth): void {
                            $sameYear->where('year', $toYear)
                                ->where('month', '<=', $toMonth ?: 12);
                        });
                });
            });
    }

    private function periodParts(?string $value): array
    {
        if (! $value) {
            return [null, null];
        }

        $value = str_replace(['-', '.'], '/', trim($value));

        if (preg_match('/^(\d{4})\/(\d{1,2})/', $value, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        if (preg_match('/^(\d{4})(\d{2})/', $value, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [null, null];
    }
}
