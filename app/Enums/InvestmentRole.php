<?php

namespace App\Enums;

enum InvestmentRole: string
{
    case SuperAdmin = 'superadmin';
    case Manager = 'manager';
    case SeniorInvestmentExpert = 'senior_investment_expert';
    case Expert = 'expert';
    case Observer = 'observer';
    case InvesteeRepresentative = 'investee_representative';
    case Evaluator = 'evaluator';
    case ExecutiveBoard = 'executive_board';
    case FinanceManagement = 'finance_management';
    case InvestmentManagement = 'investment_management';
    case PortfolioAffairsManagement = 'portfolio_affairs_management';
    case AdministrativeSupportManagement = 'administrative_support_management';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'ادمین',
            self::Manager => 'مدیر',
            self::SeniorInvestmentExpert => 'کارشناس ارشد سرمایه‌گذاری',
            self::Expert => 'کارشناس',
            self::Observer => 'ناظر',
            self::InvesteeRepresentative => 'نماینده سرمایه‌پذیر',
            self::Evaluator => 'ارزیاب',
            self::ExecutiveBoard => 'مدیرعامل و اعضای هیأت مدیره',
            self::FinanceManagement => 'مدیریت مالی',
            self::InvestmentManagement => 'مدیریت سرمایه‌گذاری',
            self::PortfolioAffairsManagement => 'مدیریت امور مجامع شرکت‌ها',
            self::AdministrativeSupportManagement => 'مدیریت اداری و پشتیبانی',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function organizationalRoles(): array
    {
        return [
            self::ExecutiveBoard,
            self::FinanceManagement,
            self::InvestmentManagement,
            self::PortfolioAffairsManagement,
            self::AdministrativeSupportManagement,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function organizationalRoleSlugs(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::organizationalRoles());
    }

    /**
     * Roles that may be explicitly assigned to a project or investment step.
     * Manager and superadmin operate globally; the investee representative never decides workflow steps.
     *
     * @return array<int, self>
     */
    public static function assignableReviewRoles(): array
    {
        return [self::Expert, self::Observer, self::Evaluator];
    }

    /**
     * @return array<int, string>
     */
    public static function decisionRoleSlugs(): array
    {
        return [self::Expert->value, self::Evaluator->value];
    }

    /**
     * @return array<int, string>
     */
    public static function assignableReviewRoleSlugs(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::assignableReviewRoles());
    }
}
