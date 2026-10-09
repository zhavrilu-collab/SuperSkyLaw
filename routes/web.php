<?php

use App\Http\Controllers\Auth\CoreOAuthCallbackController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OrganizationRegistrationController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\StaffInviteAcceptController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarFeedController;
use App\Http\Controllers\ClientAccountController;
use App\Http\Controllers\CostBillController;
use App\Http\Controllers\CourtRegisterController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\EInvoiceController;
use App\Http\Controllers\ESignController;
use App\Http\Controllers\EthicalWallController;
use App\Http\Controllers\LimitationController;
use App\Http\Controllers\MatterDeadlineController;
use App\Http\Controllers\SmsNoticeController;
use App\Http\Controllers\SpnftController;
use App\Http\Controllers\TrustAccountController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MailIntakeController;
use App\Http\Controllers\MatterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfficeSettingsController;
use App\Http\Controllers\OrganizationDashboardController;
use App\Http\Controllers\OrganizationPickerController;
use App\Http\Controllers\OrganizationSuspendedController;
use App\Http\Controllers\OrganizationTeamController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\Portal\HomeController as PortalHomeController;
use App\Http\Controllers\Portal\LoginController as PortalLoginController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/kalendar/feed/{token}', [CalendarFeedController::class, 'publicFeed'])
    ->where('token', '[A-Za-z0-9]{20,64}')
    ->name('calendar.feed');

Route::prefix('{slug}/portal')
    ->where(['slug' => '[a-z0-9\-]+'])
    ->group(function () {
        Route::get('/prijava', [PortalLoginController::class, 'create'])->name('portal.login');
        Route::post('/prijava', [PortalLoginController::class, 'store'])->middleware('throttle:6,1');
        Route::middleware('client.office')->group(function () {
            Route::post('/odjava', [PortalLoginController::class, 'destroy'])->name('portal.logout');
            Route::get('/', [PortalHomeController::class, 'index'])->name('portal.home');
            Route::get('/predmeti/{matter}', [PortalHomeController::class, 'show'])->whereNumber('matter')->name('portal.matters.show');
            Route::get('/racuni/{invoice}/pdf', [PortalHomeController::class, 'invoice'])->whereNumber('invoice')->name('portal.invoices.pdf');
            Route::get('/dokumenti/{document}', [PortalHomeController::class, 'document'])->whereNumber('document')->name('portal.documents.download');
        });
    });

Route::middleware('guest')->group(function () {
    Route::get('/prijava', [LoginController::class, 'create'])->name('login');
    Route::post('/prijava', [LoginController::class, 'store']);
    Route::get('/zaboravljena-lozinka', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/zaboravljena-lozinka', [PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/resetiranje-lozinke/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/resetiranje-lozinke', [NewPasswordController::class, 'store'])->name('password.store')->middleware('throttle:6,1');
    Route::get('/auth/core/callback', [CoreOAuthCallbackController::class, 'create'])->name('auth.core.callback');
    Route::get('/poziv/{token}', [StaffInviteAcceptController::class, 'show'])->name('staff-invite.show');
    Route::post('/poziv/{token}', [StaffInviteAcceptController::class, 'store'])->name('staff-invite.store');
});

Route::get('/registracija', [OrganizationRegistrationController::class, 'create'])->name('register.organization');
Route::post('/registracija', [OrganizationRegistrationController::class, 'store'])
    ->middleware('throttle:6,1');

Route::middleware('auth')->group(function () {
    Route::post('/odjava', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/registracija-ceka', [OrganizationRegistrationController::class, 'pending'])->name('registration.pending');
    Route::get('/registracija-ceka/stanje', [OrganizationRegistrationController::class, 'status'])->name('registration.pending.status');
    Route::get('/odabir-tvrtke', [OrganizationPickerController::class, 'index'])->name('organization.pick');
    Route::post('/odabir-tvrtke', [OrganizationPickerController::class, 'store'])->name('organization.pick.store');
    Route::get('/pristup-suspendiran/{slug}', [OrganizationSuspendedController::class, 'show'])->name('organization.suspended');
});

Route::prefix('{slug}')
    ->where(['slug' => '[a-z0-9\-]+'])
    ->middleware(['auth', 'organization'])
    ->group(function () {
        Route::get('/', OrganizationDashboardController::class)->name('organization.dashboard');
        Route::get('/ured', [OfficeSettingsController::class, 'edit'])->name('organization.settings.edit');
        Route::put('/ured', [OfficeSettingsController::class, 'update'])->name('organization.settings.update');
        Route::get('/postavke', [OfficeSettingsController::class, 'appearance'])->name('organization.settings.appearance');
        Route::put('/postavke/tema', [OfficeSettingsController::class, 'updateTheme'])->name('organization.settings.theme');
        Route::get('/tim', [OrganizationTeamController::class, 'index'])->name('organization.team.index');
        Route::post('/tim/pozivnice', [OrganizationTeamController::class, 'storeInvite'])->name('organization.team.invite');
        Route::patch('/tim/{member}/uloga', [OrganizationTeamController::class, 'updateRole'])->name('organization.team.update-role');
        Route::delete('/tim/{member}', [OrganizationTeamController::class, 'destroyMember'])->name('organization.team.destroy');

        Route::get('/predmeti', [MatterController::class, 'index'])->name('organization.matters.index');
        Route::get('/predmeti/novi', [MatterController::class, 'create'])->name('organization.matters.create');
        Route::post('/predmeti', [MatterController::class, 'store'])->name('organization.matters.store');
        Route::get('/predmeti/{matter}', [MatterController::class, 'show'])->whereNumber('matter')->name('organization.matters.show');
        Route::put('/predmeti/{matter}', [MatterController::class, 'update'])->whereNumber('matter')->name('organization.matters.update');
        Route::delete('/predmeti/{matter}', [MatterController::class, 'destroy'])->whereNumber('matter')->name('organization.matters.destroy');
        Route::post('/predmeti/{matter}/stranke', [MatterController::class, 'attachParty'])->whereNumber('matter')->name('organization.matters.parties.store');
        Route::post('/predmeti/{matter}/kronologija', [MatterController::class, 'storeTimeline'])->whereNumber('matter')->name('organization.matters.timeline.store');
        Route::post('/predmeti/{matter}/zid', [EthicalWallController::class, 'store'])->whereNumber('matter')->name('organization.matters.walls.store');
        Route::delete('/predmeti/{matter}/zid/{wall}', [EthicalWallController::class, 'destroy'])->whereNumber('matter')->whereNumber('wall')->name('organization.matters.walls.destroy');
        Route::post('/predmeti/{matter}/spnft', [SpnftController::class, 'required'])->whereNumber('matter')->name('organization.matters.spnft.required');
        Route::post('/predmeti/{matter}/spnft/stavka', [SpnftController::class, 'toggle'])->whereNumber('matter')->name('organization.matters.spnft.toggle');
        Route::post('/predmeti/{matter}/zastara', [LimitationController::class, 'store'])->whereNumber('matter')->name('organization.matters.limitation.store');
        Route::post('/predmeti/{matter}/rok', [MatterDeadlineController::class, 'store'])->whereNumber('matter')->name('organization.matters.deadline.store');

        Route::get('/stranke', [PartyController::class, 'index'])->name('organization.parties.index');
        Route::get('/stranke/nova', [PartyController::class, 'create'])->name('organization.parties.create');
        Route::post('/stranke', [PartyController::class, 'store'])->name('organization.parties.store');
        Route::get('/stranke/{party}/uredi', [PartyController::class, 'edit'])->whereNumber('party')->name('organization.parties.edit');
        Route::put('/stranke/{party}', [PartyController::class, 'update'])->whereNumber('party')->name('organization.parties.update');
        Route::post('/stranke/{party}/sudski-registar', [CourtRegisterController::class, 'lookup'])->whereNumber('party')->name('organization.parties.registry');
        Route::post('/stranke/{party}/portal', [ClientAccountController::class, 'store'])->whereNumber('party')->name('organization.parties.portal');

        Route::get('/kalendar', [CalendarController::class, 'index'])->name('organization.calendar.index');
        Route::post('/kalendar', [CalendarController::class, 'store'])->name('organization.calendar.store');
        Route::post('/kalendar/{event}/zavrsi', [CalendarController::class, 'complete'])->whereNumber('event')->name('organization.calendar.complete');
        Route::post('/kalendar/{event}/tarifa', [CalendarController::class, 'charge'])->whereNumber('event')->name('organization.calendar.charge');
        Route::post('/kalendar/{event}/sms', [SmsNoticeController::class, 'store'])->whereNumber('event')->name('organization.calendar.sms');
        Route::get('/kalendar/izvoz.ics', [CalendarFeedController::class, 'export'])->name('organization.calendar.export');
        Route::post('/kalendar/uvoz', [CalendarFeedController::class, 'import'])->name('organization.calendar.import');

        Route::get('/vrijeme', [TimeEntryController::class, 'index'])->name('organization.time.index');
        Route::post('/vrijeme', [TimeEntryController::class, 'store'])->name('organization.time.store');
        Route::post('/vrijeme/start', [TimeEntryController::class, 'start'])->name('organization.time.start');
        Route::post('/vrijeme/{entry}/stop', [TimeEntryController::class, 'stop'])->whereNumber('entry')->name('organization.time.stop');
        Route::post('/vrijeme/{entry}/odobri', [TimeEntryController::class, 'approve'])->whereNumber('entry')->name('organization.time.approve');
        Route::post('/vrijeme/{entry}/otpis', [TimeEntryController::class, 'writeOff'])->whereNumber('entry')->name('organization.time.write-off');
        Route::post('/vrijeme/{entry}/nenaplativo', [TimeEntryController::class, 'nonBillable'])->whereNumber('entry')->name('organization.time.non-billable');

        Route::get('/financije', [InvoiceController::class, 'index'])->name('organization.invoices.index');
        Route::post('/financije/troskovi', [InvoiceController::class, 'storeExpense'])->name('organization.expenses.store');
        Route::post('/financije/racuni', [InvoiceController::class, 'store'])->name('organization.invoices.store');
        Route::get('/financije/racuni/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('organization.invoices.show');
        Route::post('/financije/racuni/{invoice}/uplate', [InvoiceController::class, 'pay'])->whereNumber('invoice')->name('organization.invoices.pay');
        Route::get('/financije/racuni/{invoice}/pdf', [InvoiceController::class, 'pdf'])->whereNumber('invoice')->name('organization.invoices.pdf');
        Route::post('/financije/racuni/{invoice}/eracun', [EInvoiceController::class, 'store'])->whereNumber('invoice')->name('organization.invoices.einvoice');
        Route::get('/financije/tarifa', [TariffController::class, 'index'])->name('organization.tariff.index');
        Route::post('/financije/tarifa', [TariffController::class, 'store'])->name('organization.tariff.store');
        Route::get('/financije/troskovnik', [CostBillController::class, 'index'])->name('organization.cost-bills.index');
        Route::get('/financije/troskovnik/{matter}/pdf', [CostBillController::class, 'pdf'])->whereNumber('matter')->name('organization.cost-bills.pdf');
        Route::get('/financije/izvjestaj', [ReportController::class, 'index'])->name('organization.reports.index');
        Route::get('/financije/depozit', [TrustAccountController::class, 'index'])->name('organization.trust.index');
        Route::post('/financije/depozit', [TrustAccountController::class, 'store'])->name('organization.trust.store');

        Route::get('/dokumenti', [DocumentController::class, 'index'])->name('organization.documents.index');
        Route::post('/dokumenti', [DocumentController::class, 'store'])->name('organization.documents.store');
        Route::get('/dokumenti/predlosci', [DocumentTemplateController::class, 'index'])->name('organization.templates.index');
        Route::post('/dokumenti/predlosci', [DocumentTemplateController::class, 'generate'])->name('organization.templates.generate');
        Route::post('/dokumenti/{document}/podijeli', [DocumentController::class, 'share'])->whereNumber('document')->name('organization.documents.share');
        Route::post('/dokumenti/{document}/potpis', [ESignController::class, 'store'])->whereNumber('document')->name('organization.documents.sign');
        Route::get('/dokumenti/{document}/preuzmi', [DocumentController::class, 'download'])->whereNumber('document')->name('organization.documents.download');
        Route::post('/dokumenti/{document}/verzija', [DocumentController::class, 'storeVersion'])->whereNumber('document')->name('organization.documents.version');
        Route::get('/dokumenti/{document}/verzija/{version}', [DocumentController::class, 'downloadVersion'])->whereNumber('document')->whereNumber('version')->name('organization.documents.version.download');
        Route::delete('/dokumenti/{document}', [DocumentController::class, 'destroy'])->whereNumber('document')->name('organization.documents.destroy');

        Route::get('/posta', [MailIntakeController::class, 'index'])->name('organization.mail.index');
        Route::post('/posta', [MailIntakeController::class, 'store'])->name('organization.mail.store');

        Route::get('/obavijesti', [NotificationController::class, 'index'])->name('organization.notifications.index');
    });
