<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_statements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->comment('طرح ');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->unsignedTinyInteger('month')->nullable()->index();
            $table->string('net_sales')->nullable()->comment('فروش خالص ');
            $table->string('operating_revenue')->nullable()->comment('درآمدهای عملیاتی ');
            $table->string('cogs_goods')->nullable()->comment('بهای تمام شده کالای فروش رفته ');
            $table->string('cogs_services')->nullable()->comment('بهای تمام شده درآمدهای عملیاتی ');
            $table->string('gross_profit')->nullable()->comment('سود ناخالص ');
            $table->string('selling_general_admin_expense')->nullable()->comment('هزینه های فروش ،اداری و عمومی ');
            $table->string('operating_loss')->nullable()->comment('زیان عملیاتی ');
            $table->string('financial_expense')->nullable()->comment('هزینه های مالی ');
            $table->string('other_income')->nullable()->comment('سایر درآمدها ');
            $table->string('non_operating_net')->nullable()->comment('سایر درآمدها و هزینه های غیر عملیاتی ');
            $table->string('profit_before_tax')->nullable()->comment('سود(زيان)  قبل از مالیات ');
            $table->string('income_tax_expense')->nullable()->comment('هزینه مالیات بردرآمد ');
            $table->string('net_profit')->nullable()->comment('سود(زيان) خالص ');

            // Non-current Assets
            $table->string('tangible_fixed_assets')->nullable()->comment('دارایی‌های ثابت مشهود ');
            $table->string('intangible_assets')->nullable()->comment('دارایی‌های نامشهود ');
            $table->string('other_assets')->nullable()->comment('سایر دارایی ها ');
            $table->string('total_non_current_assets')->nullable()->comment('جمع دارایی‌های غير جاري ');

            // Current Assets
            $table->string('prepayments')->nullable()->comment('پیش پرداخت‌ها ');
            $table->string('inventory')->nullable()->comment('موجودی مواد وکالا ');
            $table->string('trade_receivables')->nullable()->comment('دریافتنی‌های تجاری و سایر دریافتنی‌ها  ');
            $table->string('other_receivables')->nullable()->comment('موجودي نقد ');
            $table->string('cash_and_equivalents')->nullable()->comment('جمع دارایي‌هاي جاري ');
            $table->string('total_current_assets')->nullable()->comment('جمع دارایی‌ها ');

            $table->string('total_assets')->nullable()->comment('سرمایه ');

            // Equity
            $table->string('capital')->nullable()->comment('سرمایه در جریان ');
            $table->string('capital_in_progress')->nullable()->comment('اندوخته قانوني ');
            $table->string('legal_reserve')->nullable()->comment('(زيان)انباشته ');
            $table->string('retained_earnings')->nullable()->comment('جمع حقوق مالکانه  ');
            $table->string('total_equity')->nullable()->comment('پرداختنی‌های بلند مدت به موسسه تحقیق و توسعه دانشمند ');

            // Non-current Liabilities
            $table->string('long_term_rnd_payable')->nullable()->comment('تسهیلات مالی بلند مدت ');
            $table->string('long_term_loans')->nullable()->comment('ذخيره مزاياي پايان خدمت كاركنان ');
            $table->string('employee_benefit_reserve')->nullable()->comment('پرداختنی های تجاری و سایر پرداختنی(غیر تجاری) ');
            $table->string('total_non_current_liabilities')->nullable()->comment('جمع بدهي‌هاي غير جاري ');

            // Current Liabilities
            $table->string('trade_payables')->nullable()->comment('پرداختنی‌های تجاری و سایر پرداختنی‌ها  ');
            $table->string('tax_payable')->nullable()->comment('مالیات پرداختنی ');
            $table->string('short_term_loans')->nullable()->comment('تسهیلات مالی ');
            $table->string('advances_received')->nullable()->comment('پیش دریافت ها ');
            $table->string('total_current_liabilities')->nullable()->comment('جمع بدهي‌هاي جاري ');

            $table->string('total_liabilities')->nullable()->comment('جمع بدهي‌ها ');
            $table->string('total_equity_and_liabilities')->nullable()->comment('جمع حقوق مالکانه و بدهی‌ها  ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_statements');
    }
};
