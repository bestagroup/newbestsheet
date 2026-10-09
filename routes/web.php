<?php

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\FullRegisterController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Middleware\EnsureSystemAdministrator;
use Illuminate\Support\Facades\Route;

Route::middleware('admin')->namespace('App\Http\Controllers\Panel')->group(function () {
    Route::get('panel/media/{media}/download', 'MediaDownloadController')
        ->middleware('throttle:120,1')
        ->name('media.download');
    Route::get('panel/message-attachments/{attachment}/download', 'MessageAttachmentDownloadController')
        ->middleware('throttle:120,1')
        ->name('message-attachments.download');
    Route::get('panel/projects/{project}/quarterly-performance', 'QuarterlyPerformanceController@index')
        ->name('quarterly-performance.index');
    Route::post('panel/projects/{project}/quarterly-performance', 'QuarterlyPerformanceController@store')
        ->middleware('throttle:30,1')
        ->name('quarterly-performance.store');
    Route::patch(
        'panel/projects/{project}/quarterly-performance/{report}/review',
        'QuarterlyPerformanceController@review'
    )->name('quarterly-performance.review');
    Route::get('/', 'IndexController@index');
    Route::get('dashboard', 'IndexController@index')->name('dashboard');
    Route::get('panel/getcities/{id}', 'IndexController@getcities')->name('getcities');
    Route::middleware('resource.permission:finance')->group(function () {
        Route::resource('panel/finance', 'FinancialController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('investment.manager')->group(function () {
        Route::resource('panel/owner', 'OwnerController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('resource.permission:menupanel')->group(function () {
        Route::resource('panel/menupanel', 'MenupanelController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('resource.permission:submenupanel')->group(function () {
        Route::resource('panel/submenupanel', 'SubmenupanelController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('investment.manager')->group(function () {
        Route::resource('panel/menusite', 'MenusiteController')->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::resource('panel/submenusite', 'SubmenusiteController')->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::resource('panel/typeuser', 'TypeuserController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('resource.permission:siteuser')->group(function () {
        Route::resource('panel/siteuser', 'SiteuserController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('resource.permission:paneluser')->group(function () {
        Route::resource('panel/paneluser', 'PaneluserController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware(['resource.permission:roleuser', EnsureSystemAdministrator::class])->group(function () {
        Route::resource('panel/roleuser', 'RoleuserController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware('investment.manager')->group(function () {
        Route::resource('panel/leveluser', 'LeveluserController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::middleware(['resource.permission:useraccess', EnsureSystemAdministrator::class])->group(function () {
        Route::resource('panel/useraccess', 'UseraccessController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::get('panel/archive-trash', 'FilemanagerController@trash')->middleware('submenu.permission:delete,filemanager')->name('archive.trash');
    Route::post('panel/archive-trash/{id}/restore', 'FilemanagerController@restore')->middleware('submenu.permission:delete,filemanager')->name('archive.restore');
    Route::middleware('resource.permission:filemanager')->group(function () {
        Route::resource('panel/filemanager', 'FilemanagerController')->only(['index', 'store', 'show', 'edit', 'update', 'destroy']);
    });
    Route::middleware('resource.permission:project')->group(function () {
        Route::resource('panel/project', 'ProjectController')->only(['index', 'store', 'show', 'edit', 'update', 'destroy']);
    });
    Route::middleware('resource.permission:finance')->group(function () {
        Route::resource('panel/paidmanage', 'PaidController')->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::resource('panel/receivemanage', 'ReceiveController')->only(['index']);
    });
    Route::middleware('resource.permission:company')->group(function () {
        Route::resource('panel/company', 'CompanyController')
            ->only(['index', 'store', 'edit', 'update', 'destroy'])
            ->names([
                'index' => 'panel.company.index',
                'store' => 'panel.company.store',
                'edit' => 'panel.company.edit',
                'update' => 'panel.company.update',
                'destroy' => 'panel.company.destroy',
            ]);
        Route::resource('company', 'CompanyController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });
    Route::get('minute/{id}/download', 'MinuteController@download')->middleware('submenu.permission:view,flow')->name('minute.download');
    Route::get('minute', 'MinuteController@index')->middleware('submenu.permission:view,flow')->name('minute.index');
    Route::post('minute', 'MinuteController@store')->middleware('submenu.permission:edit,flow')->name('minute.store');
    Route::get('minute/{id}/edit', 'MinuteController@edit')->middleware('submenu.permission:edit,flow')->name('minute.edit');
    Route::patch('minute/{id}', 'MinuteController@update')->middleware('submenu.permission:edit,flow')->name('minute.update');
    Route::delete('minute/{id}', 'MinuteController@destroy')->middleware('submenu.permission:edit,flow')->name('minute.destroy');
    Route::middleware('resource.permission:flow,edit')->group(function () {
        Route::resource('panel/flow', 'FlowController')->only(['index', 'store', 'show', 'edit', 'destroy']);
    });
    Route::middleware(['submenu.permission:edit,flow', 'investment.manager:stage'])->group(function () {
        Route::post('panel/flow/{project}/assignments', 'ProjectAssignmentController@store')->name('flow.assignments.store');
        Route::delete('panel/flow/{project}/assignments/{assignment}', 'ProjectAssignmentController@destroy')->name('flow.assignments.destroy');
    });

    Route::middleware(['submenu.permission:edit,flow', 'investment.manager:investment'])->group(function () {
        Route::post('panel/flow/{project}/members', 'ProjectMemberController@store')->name('flow.members.store');
        Route::patch('panel/flow/{project}/members/{member}', 'ProjectMemberController@update')->name('flow.members.update');
        Route::delete('panel/flow/{project}/members/{member}', 'ProjectMemberController@destroy')->name('flow.members.destroy');

        Route::post('panel/flow/{project}/contracts', 'ProjectContractController@store')->name('flow.contracts.store');
        Route::patch('panel/flow/{project}/contracts/{contract}', 'ProjectContractController@update')->name('flow.contracts.update');
        Route::delete('panel/flow/{project}/contracts/{contract}', 'ProjectContractController@destroy')->name('flow.contracts.destroy');
    });

    Route::middleware(['submenu.permission:edit,flow', 'investment.manager:portfolio'])->group(function () {
        Route::post('panel/flow/{project}/kpis', 'ProjectKpiController@store')->name('flow.kpis.store');
        Route::delete('panel/flow/{project}/kpis/{kpi}', 'ProjectKpiController@destroy')->name('flow.kpis.destroy');

        Route::post('panel/flow/{project}/commitments', 'ProjectCommitmentController@store')->name('flow.commitments.store');
        Route::patch('panel/flow/{project}/commitments/{projectCommitment}', 'ProjectCommitmentController@update')->name('flow.commitments.update');
        Route::delete('panel/flow/{project}/commitments/{projectCommitment}', 'ProjectCommitmentController@destroy')->name('flow.commitments.destroy');
    });

    Route::middleware('submenu.permission:edit,flow')->group(function () {
        Route::patch('panel/flow/{project}/kpis/{kpi}', 'ProjectKpiController@update')->name('flow.kpis.update');
        Route::patch('panel/flow/{project}/kpis/{kpi}/review', 'ProjectKpiController@review')->name('flow.kpis.review');
    });

    Route::middleware(['submenu.permission:edit,flow', 'investment.manager'])->group(function () {
        Route::patch('panel/investsteps/weights', 'InvestmentStepWeightController@update')->name('investsteps.weights.update');

        Route::post('panel/investsteps/{investStep}/document-requirements', 'InvestmentStepDocumentRequirementController@store')->name('investsteps.documents.store');
        Route::patch('panel/investsteps/{investStep}/document-requirements/{requirement}', 'InvestmentStepDocumentRequirementController@update')->name('investsteps.documents.update');
        Route::delete('panel/investsteps/{investStep}/document-requirements/{requirement}', 'InvestmentStepDocumentRequirementController@destroy')->name('investsteps.documents.destroy');
        Route::post('panel/investsteps/{investStep}/forms', 'StageFormDefinitionController@store')
            ->name('investsteps.forms.store');

    });
    Route::post('panel/flow/{project}/stages/{stage}/comments', 'ProjectStageCommentController@store')
        ->middleware('submenu.permission:edit,flow')
        ->name('flow.stages.comments.store');
    Route::post(
        'panel/projects/{project}/stages/{stage}/forms/{definition}/submissions',
        'ProjectStageFormSubmissionController@store'
    )->name('project-stage-forms.submissions.store');
    Route::get('panel/report/experts/data', 'ReportController@expertsData')
        ->middleware('submenu.permission:view,report')
        ->name('report.experts.data');
    Route::get('panel/report/export/{type}', 'ReportController@export')
        ->middleware('submenu.permission:view,report')
        ->whereIn('type', ['portfolio', 'sla', 'experts', 'performance'])
        ->name('report.export');
    Route::middleware('resource.permission:report')->group(function () {
        Route::resource('panel/report', 'ReportController')->only(['index', 'show']);
    });
    Route::get('correspondence/data', 'CorrespondenceController@data')->name('correspondence.data');
    Route::post('correspondence/{conversation}/read', 'CorrespondenceController@markRead')->name('correspondence.read');
    Route::get('correspondence', 'CorrespondenceController@index')->name('correspondence.index');
    Route::post('correspondence', 'CorrespondenceController@store')->name('correspondence.store');
    Route::get('correspondence/{id}', 'CorrespondenceController@show')
        ->middleware('submenu.permission:view,flow')
        ->name('correspondence.show');
    Route::middleware('resource.permission:financialstatement')->group(function () {
        Route::resource('panel/financialstatement', 'FinancialstatementController')->only(['index', 'store', 'edit', 'update', 'destroy']);
    });

    Route::middleware('resource.permission:employees')->group(function () {
        Route::resource('panel/employees', 'EmployeeController')
            ->parameters(['employees' => 'employee'])
            ->only(['index', 'store', 'update', 'destroy']);
    });
    Route::post('panel/employees/{employee}/documents', 'EmployeeController@storeDocument')
        ->middleware('submenu.permission:insert,employees')
        ->name('employees.documents.store');
    Route::get('panel/employees/{employee}/documents/{document}/download', 'EmployeeController@downloadDocument')
        ->middleware(['submenu.permission:view,employees', 'throttle:120,1'])
        ->name('employees.documents.download');
    Route::delete('panel/employees/{employee}/documents/{document}', 'EmployeeController@destroyDocument')
        ->middleware('submenu.permission:delete,employees')
        ->name('employees.documents.destroy');

    Route::middleware('resource.permission:assets')->group(function () {
        Route::resource('panel/assets', 'AdministrativeAssetController')
            ->parameters(['assets' => 'asset'])
            ->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('resource.permission:meetings')->group(function () {
        Route::resource('panel/meetings', 'PortfolioMeetingController')->only(['index', 'store', 'show', 'update']);
    });
    Route::post('panel/meetings/{meeting}/transition', 'PortfolioMeetingController@transition')->middleware('submenu.permission:edit,meetings')->name('meetings.transition');
    Route::post('panel/meetings/{meeting}/resolutions', 'PortfolioMeetingController@resolution')->middleware('submenu.permission:edit,meetings')->name('meetings.resolutions.store');
    Route::patch('panel/meetings/{meeting}/resolutions/{resolution}', 'PortfolioMeetingController@complete')->middleware('submenu.permission:edit,meetings')->name('meetings.resolutions.complete');
    Route::middleware('resource.permission:letters')->group(function () {
        Route::resource('panel/letters', 'ExternalLetterController')->only(['index', 'store', 'show', 'update']);
    });
    Route::get('panel/letters/{letter}/attachment', 'ExternalLetterController@attachment')->middleware('submenu.permission:view,letters')->name('letters.attachment');
    Route::post('panel/letters/{letter}/transition', 'ExternalLetterController@transition')->middleware('submenu.permission:edit,letters')->name('letters.transition');

    Route::get('profile', 'ProfileController@index')->name('profile');
    Route::patch('panel/profile/user', 'ProfileController@updateUser')->name('profile.user.update');
    Route::patch('panel/profile/company-project', 'ProfileController@updateCompany')->name('profile.company-project.update');

    Route::get('panel/profile/sales', 'InvesteeSaleController@index')->name('profile.sales.index');
    Route::post('panel/profile/sales', 'InvesteeSaleController@store')->name('profile.sales.store');
    Route::get('panel/profile/sales/{sale}/edit', 'InvesteeSaleController@edit')->name('profile.sales.edit');
    Route::patch('panel/profile/sales/{sale}', 'InvesteeSaleController@update')->name('profile.sales.update');
    Route::delete('panel/profile/sales/{sale}', 'InvesteeSaleController@destroy')->name('profile.sales.destroy');

    Route::post('panel/profile/documents', 'InvesteeDocumentController@store')->name('profile.documents.store');
    Route::delete('panel/profile/documents/{media}', 'InvesteeDocumentController@destroy')->name('profile.documents.destroy');

    Route::post('panel/profile/members', 'InvesteeMemberController@store')->name('profile.members.store');
    Route::get('panel/profile/members/{member}/edit', 'InvesteeMemberController@edit')->name('profile.members.edit');
    Route::patch('panel/profile/members/{member}', 'InvesteeMemberController@update')->name('profile.members.update');
    Route::delete('panel/profile/members/{member}', 'InvesteeMemberController@destroy')->name('profile.members.destroy');

    Route::get('panel/calendar', 'CalendarController@index')->middleware('submenu.permission:view,calendar')->name('calendar.index');
    Route::get('panel/calendar/events', 'CalendarController@getEvents')->middleware('submenu.permission:view,calendar')->name('calendar.events');
    Route::post('panel/calendar/store', 'CalendarController@store')->middleware('submenu.permission:insert,calendar')->name('calendar.store');
    Route::patch('panel/calendar/update/{id}', 'CalendarController@update')->middleware('submenu.permission:edit,calendar')->name('calendar.update');
    Route::delete('panel/calendar/delete/{id}', 'CalendarController@destroy')->middleware('submenu.permission:delete,calendar')->name('calendar.destroy');

    Route::get('panel/notifications', 'NotificationController@index')->name('notifications.index');
    Route::get('panel/notifications/data', 'NotificationController@data')->name('notifications.data');
    Route::post('panel/notifications/read-all', 'NotificationController@readAll')->name('notifications.read-all');
    Route::post('panel/notifications/{id}/read', 'NotificationController@read')->name('notifications.read');

    Route::get('panel/activity-logs', 'ActivityLogController@index')
        ->middleware('resource.permission:report')
        ->name('activitylog.index');

    Route::get('panel/changepassword', 'ChangePasswordController@index')->name('password.change.form');
    Route::post('panel/changepassword', 'ChangePasswordController@change')->name('password.change.submit');

    Route::post('panel/filestatus', 'FilemanagerController@filestatus')->middleware('submenu.permission:edit,filemanager')->name('filestatus');
    Route::post('panel/store', 'FilemanagerController@store')->middleware('submenu.permission:insert,filemanager')->name('storemedia');
    Route::get('panel/selectfile', 'FilemanagerController@selectfile')->middleware('submenu.permission:view,filemanager')->name('selectfile');
    Route::delete('panel/deletefile', 'FilemanagerController@deletefile')->middleware('submenu.permission:delete,filemanager')->name('deletefile');

    Route::post('panel/toggle-theme', 'ThemeController@toggle')->name('toggle-theme');

    /* charts */

});

Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])
        ->name('password.confirm');
    Route::post('password/confirm', [ConfirmPasswordController::class, 'confirm']);
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])
        ->name('login');
    Route::post('login', [LoginController::class, 'login']);

    Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');
    Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('password/reset', [ResetPasswordController::class, 'reset'])
        ->name('password.update');

    Route::get('register', [RegisterController::class, 'showRegistrationForm'])
        ->name('register');
    Route::post('panel/fullregister', [FullRegisterController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('fullregister');

    Route::get('login/google', [GoogleController::class, 'redirectToGoogle'])
        ->middleware('throttle:10,1')
        ->name('redirectToProvider');
    Route::get('login/google/callback', [GoogleController::class, 'handleGoogleCallback'])
        ->middleware('throttle:10,1')
        ->name('handleProviderCallback');

    Route::get('otplogin', [LoginController::class, 'otplogin'])->name('otplogin');
    Route::post('gettoken', [LoginController::class, 'gettoken'])
        ->middleware('throttle:5,1')
        ->name('gettoken');
    Route::get('sendtoken', [LoginController::class, 'sendtoken'])->name('sendtoken');
    Route::post('checktoken', [LoginController::class, 'checktoken'])
        ->middleware('throttle:15,1')
        ->name('checktoken');
});
