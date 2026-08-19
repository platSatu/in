<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileBussinesController;
use App\Http\Controllers\Quiz\CityController;
use App\Http\Controllers\Quiz\MajorController;
use App\Http\Controllers\Quiz\SettingUniversityController;
use App\Http\Controllers\Quiz\UniversityAlbumController;
use App\Http\Controllers\Quiz\UniversityAlbumPhotoController;
use App\Http\Controllers\Quiz\WhatsappTemplateController;
use App\Http\Controllers\Qrcode\LinkToQrcodeController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\Dashboard\PackageController as DashboardPackageController;
use App\Http\Controllers\Dashboard\DepositController as DashboardDepositController;
use App\Http\Controllers\Dashboard\HistoryUserController as DashboardHistoryUserController;
use App\Http\Controllers\Dashboard\VoucherController as DashboardVoucherController;
use App\Http\Controllers\CategoryApplicationController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\HistoryUserLoginController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoleUserController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\Quiz\FormController;
use App\Http\Controllers\Quiz\FormQuestionController;
use App\Http\Controllers\Quiz\FormQuestionOptionController;
use App\Http\Controllers\Quiz\FormSubmissionController;
use App\Http\Controllers\Quiz\FormAnswerController;
use App\Http\Controllers\Quiz\UniversityController;
use App\Http\Controllers\Quiz\UniversityProfileController;
use App\Http\Controllers\Pembayaran\PembayaranCategoriesController;
use App\Http\Controllers\Pembayaran\PembayaranFormsController;
use App\Http\Controllers\Pembayaran\PembayaranFormLinksController;
use App\Http\Controllers\Absensi\AttendanceController;
use App\Http\Controllers\Absensi\AttendanceSettingController;
use App\Http\Controllers\Absensi\AttendanceUserQrCodeController;
use App\Http\Controllers\Absensi\AcademicCalendarController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\BackendInvitationController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Company\CompanyProfileController;
use App\Http\Controllers\Company\CompanyBranchController;
use App\Http\Controllers\Company\CompanyDivisionController;
use App\Http\Controllers\Settings\PaymentGatewayController;
use App\Http\Controllers\Settings\WhatsappGatewayController;
use App\Http\Controllers\Payment\FormPaymentController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return redirect()->route('login');
// });


Route::get('/', [FrontendController::class, 'index'])->name('home');

Route::get('/frontend/universities-by-city/{city}', [FrontendController::class, 'universitiesByCity'])
    ->name('frontend.universities-by-city');

// Frontend Form Wizard Routes
Route::get('/quiz', [FrontendController::class, 'formWizard'])->name('frontend.form.wizard');
Route::post('/quiz', [FrontendController::class, 'formWizardSubmit'])->name('frontend.form.wizard.submit');
Route::get('/quiz/{branchSlug}/{boothSlug}', [FrontendController::class, 'formWizardBySlug'])->name('frontend.form.wizard.slug');

// Payment gateway routes untuk wizard publik (name/email/hp -> bayar -> placement test).
// Ini dipanggil via fetch() dari resources/views/frontend/form-wizard.blade.php.
Route::post('/quiz/payment/init', [FormPaymentController::class, 'init'])->name('frontend.payment.init');
Route::post('/quiz/payment/duitku/select-method', [FormPaymentController::class, 'selectDuitkuMethod'])->name('frontend.payment.duitku.select-method');
Route::get('/quiz/payment/{orderId}/status', [FormPaymentController::class, 'status'])->name('frontend.payment.status');
Route::get('/quiz/payment/return', [FormPaymentController::class, 'return'])->name('frontend.payment.return');

Route::get('/handbook', [FrontendController::class, 'handbook'])->name('frontend.handbook');
Route::get('/handbook/{id}/download', [FrontendController::class, 'handbookDownload'])->name('frontend.handbook.download');

Route::get('/invitation/form', [InvitationController::class, 'create'])->name('invitation.create');
Route::post('/invitation/form', [InvitationController::class, 'store'])->name('invitation.store');
Route::get('/invitation/{qrcode}', [InvitationController::class, 'show'])->name('invitation.show');

Route::get('/universities', [FrontendController::class, 'universityCatalog'])->name('frontend.university.catalog');
Route::get('/university/{id}', [FrontendController::class, 'universityProfile'])->name('frontend.university.profile');

Route::get('/packages', [DashboardPackageController::class, 'index'])->name('public.packages.index');

Route::middleware(['auth'])->group(function () {
    Route::get('/packages/{id}/checkout', [DashboardPackageController::class, 'checkout'])->name('public.packages.checkout');
    Route::post('/packages/{id}/pay', [DashboardPackageController::class, 'payWithDeposit'])->name('public.packages.pay');

    Route::get('/dashboard/deposit/create', [DashboardDepositController::class, 'create'])->name('dashboard.deposit.create');
    Route::post('/dashboard/deposit', [DashboardDepositController::class, 'store'])->name('dashboard.deposit.store');

    Route::get('/dashboard/history-user', [DashboardHistoryUserController::class, 'index'])->name('dashboard.history-user.index');

    Route::get('/dashboard/voucher/redeem', [DashboardVoucherController::class, 'redeemForm'])->name('dashboard.voucher.redeem');
    Route::post('/dashboard/voucher/redeem', [DashboardVoucherController::class, 'redeem'])->name('dashboard.voucher.redeem.submit');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->prefix('dashboard/profile-bussines')->group(function () {

    Route::get('/', [ProfileBussinesController::class, 'index'])->name('profile-bussines.index');
    Route::get('/my-data', [ProfileBussinesController::class, 'myData'])->name('profile-bussines.myData');
    Route::get('/create', [ProfileBussinesController::class, 'create'])->name('profile-bussines.create');
    Route::post('/', [ProfileBussinesController::class, 'store'])->name('profile-bussines.store');
    Route::get('/{id}', [ProfileBussinesController::class, 'show'])->name('profile-bussines.show');
    Route::get('/{id}/edit', [ProfileBussinesController::class, 'edit'])->name('profile-bussines.edit');
    Route::put('/{id}', [ProfileBussinesController::class, 'update'])->name('profile-bussines.update');
    Route::delete('/{id}', [ProfileBussinesController::class, 'destroy'])->name('profile-bussines.destroy');

});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/tenant')->group(function () {
    Route::get('/', [TenantController::class, 'index'])->name('tenant.index');
    Route::get('/create', [TenantController::class, 'create'])->name('tenant.create');
    Route::post('/', [TenantController::class, 'store'])->name('tenant.store');
    Route::get('/{id}/edit', [TenantController::class, 'edit'])->name('tenant.edit');
    Route::put('/{id}', [TenantController::class, 'update'])->name('tenant.update');
    Route::delete('/{id}', [TenantController::class, 'destroy'])->name('tenant.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/package')->group(function () {
    Route::get('/', [PackageController::class, 'index'])->name('package.index');
    Route::get('/create', [PackageController::class, 'create'])->name('package.create');
    Route::post('/', [PackageController::class, 'store'])->name('package.store');
    Route::get('/{id}/edit', [PackageController::class, 'edit'])->name('package.edit');
    Route::put('/{id}', [PackageController::class, 'update'])->name('package.update');
    Route::delete('/{id}', [PackageController::class, 'destroy'])->name('package.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/category-application')->group(function () {
    Route::get('/', [CategoryApplicationController::class, 'index'])->name('category-application.index');
    Route::get('/create', [CategoryApplicationController::class, 'create'])->name('category-application.create');
    Route::post('/', [CategoryApplicationController::class, 'store'])->name('category-application.store');
    Route::get('/{id}/edit', [CategoryApplicationController::class, 'edit'])->name('category-application.edit');
    Route::put('/{id}', [CategoryApplicationController::class, 'update'])->name('category-application.update');
    Route::delete('/{id}', [CategoryApplicationController::class, 'destroy'])->name('category-application.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/deposit')->group(function () {
    Route::get('/', [DepositController::class, 'index'])->name('deposit.index');
    Route::get('/create', [DepositController::class, 'create'])->name('deposit.create');
    Route::post('/', [DepositController::class, 'store'])->name('deposit.store');
    Route::get('/{id}/edit', [DepositController::class, 'edit'])->name('deposit.edit');
    Route::put('/{id}', [DepositController::class, 'update'])->name('deposit.update');
    Route::delete('/{id}', [DepositController::class, 'destroy'])->name('deposit.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/user')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('user.index');
    Route::get('/create', [UserController::class, 'create'])->name('user.create');
    Route::post('/', [UserController::class, 'store'])->name('user.store');
    Route::get('/{id}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/{id}', [UserController::class, 'destroy'])->name('user.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/transaction')->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->name('transaction.index');
    Route::get('/create', [TransactionController::class, 'create'])->name('transaction.create');
    Route::post('/', [TransactionController::class, 'store'])->name('transaction.store');
    Route::get('/{id}/edit', [TransactionController::class, 'edit'])->name('transaction.edit');
    Route::put('/{id}', [TransactionController::class, 'update'])->name('transaction.update');
    Route::delete('/{id}', [TransactionController::class, 'destroy'])->name('transaction.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/historyuserlogin')->group(function () {
    Route::get('/', [HistoryUserLoginController::class, 'index'])->name('historyuserlogin.index');
    Route::get('/create', [HistoryUserLoginController::class, 'create'])->name('historyuserlogin.create');
    Route::post('/', [HistoryUserLoginController::class, 'store'])->name('historyuserlogin.store');
    Route::get('/{id}/edit', [HistoryUserLoginController::class, 'edit'])->name('historyuserlogin.edit');
    Route::put('/{id}', [HistoryUserLoginController::class, 'update'])->name('historyuserlogin.update');
    Route::delete('/{id}', [HistoryUserLoginController::class, 'destroy'])->name('historyuserlogin.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/roles')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/{id}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/roleuser')->group(function () {
    Route::get('/', [RoleUserController::class, 'index'])->name('roleuser.index');
    Route::get('/create', [RoleUserController::class, 'create'])->name('roleuser.create');
    Route::post('/', [RoleUserController::class, 'store'])->name('roleuser.store');
    Route::get('/{id}/edit', [RoleUserController::class, 'edit'])->name('roleuser.edit');
    Route::put('/{id}', [RoleUserController::class, 'update'])->name('roleuser.update');
    Route::delete('/{id}', [RoleUserController::class, 'destroy'])->name('roleuser.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/vouchers')->group(function () {
    Route::get('/', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('/', [VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('/{id}/edit', [VoucherController::class, 'edit'])->name('vouchers.edit');
    Route::put('/{id}', [VoucherController::class, 'update'])->name('vouchers.update');
    Route::delete('/{id}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/quiz/form')->group(function () {
    Route::get('/', [FormController::class, 'index'])->name('quiz.form.index');
    Route::get('/create', [FormController::class, 'create'])->name('quiz.form.create');
    Route::post('/', [FormController::class, 'store'])->name('quiz.form.store');
    Route::get('/{id}/edit', [FormController::class, 'edit'])->name('quiz.form.edit');
    Route::get('/{id}/submissions', [FormController::class, 'submissions'])->name('quiz.form.submissions');
    Route::post('/submissions/{submissionId}/result', [FormController::class, 'saveResult'])->name('quiz.form.submissions.save-result');
    Route::put('/{id}', [FormController::class, 'update'])->name('quiz.form.update');
    Route::delete('/{id}', [FormController::class, 'destroy'])->name('quiz.form.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/city')->group(function () {
    Route::get('/', [CityController::class, 'index'])->name('city.index');
    Route::get('/create', [CityController::class, 'create'])->name('city.create');
    Route::post('/', [CityController::class, 'store'])->name('city.store');
    Route::get('/{id}/edit', [CityController::class, 'edit'])->name('city.edit');
    Route::put('/{id}', [CityController::class, 'update'])->name('city.update');
    Route::delete('/{id}', [CityController::class, 'destroy'])->name('city.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/major')->group(function () {
    Route::get('/', [MajorController::class, 'index'])->name('quiz.major.index');
    Route::get('/create', [MajorController::class, 'create'])->name('quiz.major.create');
    Route::post('/', [MajorController::class, 'store'])->name('quiz.major.store');
    Route::get('/{id}/edit', [MajorController::class, 'edit'])->name('quiz.major.edit');
    Route::put('/{id}', [MajorController::class, 'update'])->name('quiz.major.update');
    Route::delete('/{id}', [MajorController::class, 'destroy'])->name('quiz.major.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/setting-university')->group(function () {
    Route::get('/', [SettingUniversityController::class, 'index'])->name('quiz.setting-university.index');
    Route::get('/create', [SettingUniversityController::class, 'create'])->name('quiz.setting-university.create');
    Route::post('/', [SettingUniversityController::class, 'store'])->name('quiz.setting-university.store');
    Route::get('/{id}/edit', [SettingUniversityController::class, 'edit'])->name('quiz.setting-university.edit');
    Route::put('/{id}', [SettingUniversityController::class, 'update'])->name('quiz.setting-university.update');
    Route::delete('/{id}', [SettingUniversityController::class, 'destroy'])->name('quiz.setting-university.destroy');
});


Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/whatsapp-template')->group(function () {
    Route::get('/', [WhatsappTemplateController::class, 'index'])->name('quiz.whatsapp-template.index');
    Route::get('/create', [WhatsappTemplateController::class, 'create'])->name('quiz.whatsapp-template.create');
    Route::post('/', [WhatsappTemplateController::class, 'store'])->name('quiz.whatsapp-template.store');
    Route::get('/{id}/edit', [WhatsappTemplateController::class, 'edit'])->name('quiz.whatsapp-template.edit');
    Route::put('/{id}', [WhatsappTemplateController::class, 'update'])->name('quiz.whatsapp-template.update');
    Route::delete('/{id}', [WhatsappTemplateController::class, 'destroy'])->name('quiz.whatsapp-template.destroy');
});


Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/university-album')->group(function () {
    Route::get('/', [UniversityAlbumController::class, 'index'])->name('quiz.university-album.index');
    Route::get('/create', [UniversityAlbumController::class, 'create'])->name('quiz.university-album.create');
    Route::post('/', [UniversityAlbumController::class, 'store'])->name('quiz.university-album.store');
    Route::get('/{id}/edit', [UniversityAlbumController::class, 'edit'])->name('quiz.university-album.edit');
    Route::put('/{id}', [UniversityAlbumController::class, 'update'])->name('quiz.university-album.update');
    Route::delete('/{id}', [UniversityAlbumController::class, 'destroy'])->name('quiz.university-album.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/university-album-photo')->group(function () {
    Route::get('/', [UniversityAlbumPhotoController::class, 'index'])->name('quiz.university-album-photo.index');
    Route::get('/create', [UniversityAlbumPhotoController::class, 'create'])->name('quiz.university-album-photo.create');
    Route::post('/', [UniversityAlbumPhotoController::class, 'store'])->name('quiz.university-album-photo.store');
    Route::get('/{id}/edit', [UniversityAlbumPhotoController::class, 'edit'])->name('quiz.university-album-photo.edit');
    Route::put('/{id}', [UniversityAlbumPhotoController::class, 'update'])->name('quiz.university-album-photo.update');
    Route::delete('/{id}', [UniversityAlbumPhotoController::class, 'destroy'])->name('quiz.university-album-photo.destroy');
});


Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/generate-link-to-qrcode')->group(function () {
    Route::get('/', [LinkToQrcodeController::class, 'index'])->name('qrcodes.index');
    Route::get('/create', [LinkToQrcodeController::class, 'create'])->name('qrcodes.create');
    Route::post('/', [LinkToQrcodeController::class, 'store'])->name('qrcodes.store');
    Route::get('/{id}/edit', [LinkToQrcodeController::class, 'edit'])->name('qrcodes.edit');
    Route::put('/{id}', [LinkToQrcodeController::class, 'update'])->name('qrcodes.update');
    Route::delete('/{id}', [LinkToQrcodeController::class, 'destroy'])->name('qrcodes.destroy');
    Route::get('/{id}', [LinkToQrcodeController::class, 'show'])->name('qrcodes.show');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/quiz/form-question')->group(function () {
    Route::get('/', [FormQuestionController::class, 'index'])->name('quiz.form-question.index');
    Route::get('/create', [FormQuestionController::class, 'create'])->name('quiz.form-question.create');
    Route::post('/', [FormQuestionController::class, 'store'])->name('quiz.form-question.store');
    Route::get('/{id}/edit', [FormQuestionController::class, 'edit'])->name('quiz.form-question.edit');
    Route::put('/{id}', [FormQuestionController::class, 'update'])->name('quiz.form-question.update');
    Route::delete('/{id}', [FormQuestionController::class, 'destroy'])->name('quiz.form-question.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/quiz/form-question-option')->group(function () {
    Route::get('/', [FormQuestionOptionController::class, 'index'])->name('quiz.form-question-option.index');
    Route::get('/create', [FormQuestionOptionController::class, 'create'])->name('quiz.form-question-option.create');
    Route::post('/', [FormQuestionOptionController::class, 'store'])->name('quiz.form-question-option.store');
    Route::get('/{id}/edit', [FormQuestionOptionController::class, 'edit'])->name('quiz.form-question-option.edit');
    Route::put('/{id}', [FormQuestionOptionController::class, 'update'])->name('quiz.form-question-option.update');
    Route::delete('/{id}', [FormQuestionOptionController::class, 'destroy'])->name('quiz.form-question-option.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/quiz/form-submission')->group(function () {
    Route::get('/', [FormSubmissionController::class, 'index'])->name('quiz.form-submission.index');
    Route::get('/create', [FormSubmissionController::class, 'create'])->name('quiz.form-submission.create');
    Route::post('/', [FormSubmissionController::class, 'store'])->name('quiz.form-submission.store');
    Route::get('/{id}/edit', [FormSubmissionController::class, 'edit'])->name('quiz.form-submission.edit');
    Route::put('/{id}', [FormSubmissionController::class, 'update'])->name('quiz.form-submission.update');
    Route::delete('/{id}', [FormSubmissionController::class, 'destroy'])->name('quiz.form-submission.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/quiz/form-answer')->group(function () {
    Route::get('/', [FormAnswerController::class, 'index'])->name('quiz.form-answer.index');
    Route::get('/create', [FormAnswerController::class, 'create'])->name('quiz.form-answer.create');
    Route::post('/', [FormAnswerController::class, 'store'])->name('quiz.form-answer.store');
    Route::get('/{id}/edit', [FormAnswerController::class, 'edit'])->name('quiz.form-answer.edit');
    Route::put('/{id}', [FormAnswerController::class, 'update'])->name('quiz.form-answer.update');
    Route::delete('/{id}', [FormAnswerController::class, 'destroy'])->name('quiz.form-answer.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/quiz/university')->group(function () {
    Route::get('/', [UniversityController::class, 'index'])->name('quiz.university.index');
    Route::get('/create', [UniversityController::class, 'create'])->name('quiz.university.create');
    Route::post('/', [UniversityController::class, 'store'])->name('quiz.university.store');
    Route::get('/{id}/edit', [UniversityController::class, 'edit'])->name('quiz.university.edit');
    Route::put('/{id}', [UniversityController::class, 'update'])->name('quiz.university.update');
    Route::delete('/{id}', [UniversityController::class, 'destroy'])->name('quiz.university.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/quiz/university-profile')->group(function () {
    Route::get('/', [UniversityProfileController::class, 'index'])->name('quiz.university-profile.index');
    Route::get('/create', [UniversityProfileController::class, 'create'])->name('quiz.university-profile.create');
    Route::post('/', [UniversityProfileController::class, 'store'])->name('quiz.university-profile.store');
    Route::get('/{id}/edit', [UniversityProfileController::class, 'edit'])->name('quiz.university-profile.edit');
    Route::put('/{id}', [UniversityProfileController::class, 'update'])->name('quiz.university-profile.update');
    Route::delete('/{id}', [UniversityProfileController::class, 'destroy'])->name('quiz.university-profile.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/company/profile')->group(function () {
    Route::get('/', [CompanyProfileController::class, 'index'])->name('company.profile.index');
    Route::get('/create', [CompanyProfileController::class, 'create'])->name('company.profile.create');
    Route::post('/', [CompanyProfileController::class, 'store'])->name('company.profile.store');
    Route::get('/{id}/edit', [CompanyProfileController::class, 'edit'])->name('company.profile.edit');
    Route::put('/{id}', [CompanyProfileController::class, 'update'])->name('company.profile.update');
    Route::delete('/{id}', [CompanyProfileController::class, 'destroy'])->name('company.profile.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/company/branch')->group(function () {
    Route::get('/', [CompanyBranchController::class, 'index'])->name('company.branch.index');
    Route::get('/create', [CompanyBranchController::class, 'create'])->name('company.branch.create');
    Route::post('/', [CompanyBranchController::class, 'store'])->name('company.branch.store');
    Route::get('/{id}/edit', [CompanyBranchController::class, 'edit'])->name('company.branch.edit');
    Route::put('/{id}', [CompanyBranchController::class, 'update'])->name('company.branch.update');
    Route::delete('/{id}', [CompanyBranchController::class, 'destroy'])->name('company.branch.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/company/division')->group(function () {
    Route::get('/', [CompanyDivisionController::class, 'index'])->name('company.division.index');
    Route::get('/create', [CompanyDivisionController::class, 'create'])->name('company.division.create');
    Route::post('/', [CompanyDivisionController::class, 'store'])->name('company.division.store');
    Route::get('/{id}/edit', [CompanyDivisionController::class, 'edit'])->name('company.division.edit');
    Route::put('/{id}', [CompanyDivisionController::class, 'update'])->name('company.division.update');
    Route::delete('/{id}', [CompanyDivisionController::class, 'destroy'])->name('company.division.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/settings/payment-gateway')->group(function () {
    Route::get('/', [PaymentGatewayController::class, 'index'])->name('settings.payment-gateway.index');
    Route::get('/create', [PaymentGatewayController::class, 'create'])->name('settings.payment-gateway.create');
    Route::post('/', [PaymentGatewayController::class, 'store'])->name('settings.payment-gateway.store');
    Route::get('/{id}/edit', [PaymentGatewayController::class, 'edit'])->name('settings.payment-gateway.edit');
    Route::put('/{id}', [PaymentGatewayController::class, 'update'])->name('settings.payment-gateway.update');
    Route::put('/{id}/activate', [PaymentGatewayController::class, 'activate'])->name('settings.payment-gateway.activate');
    Route::delete('/{id}', [PaymentGatewayController::class, 'destroy'])->name('settings.payment-gateway.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/settings/whatsapp-gateway')->group(function () {
    Route::get('/', [WhatsappGatewayController::class, 'index'])->name('settings.whatsapp-gateway.index');
    Route::get('/create', [WhatsappGatewayController::class, 'create'])->name('settings.whatsapp-gateway.create');
    Route::post('/', [WhatsappGatewayController::class, 'store'])->name('settings.whatsapp-gateway.store');
    Route::get('/{id}/edit', [WhatsappGatewayController::class, 'edit'])->name('settings.whatsapp-gateway.edit');
    Route::put('/{id}', [WhatsappGatewayController::class, 'update'])->name('settings.whatsapp-gateway.update');
    Route::put('/{id}/activate', [WhatsappGatewayController::class, 'activate'])->name('settings.whatsapp-gateway.activate');
    Route::delete('/{id}', [WhatsappGatewayController::class, 'destroy'])->name('settings.whatsapp-gateway.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/pembayaran/category')->group(function () {
    Route::get('/', [PembayaranCategoriesController::class, 'index'])->name('pembayaran.category.index');
    Route::get('/create', [PembayaranCategoriesController::class, 'create'])->name('pembayaran.category.create');
    Route::post('/', [PembayaranCategoriesController::class, 'store'])->name('pembayaran.category.store');
    Route::get('/{id}/edit', [PembayaranCategoriesController::class, 'edit'])->name('pembayaran.category.edit');
    Route::put('/{id}', [PembayaranCategoriesController::class, 'update'])->name('pembayaran.category.update');
    Route::delete('/{id}', [PembayaranCategoriesController::class, 'destroy'])->name('pembayaran.category.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/pembayaran/form')->group(function () {
    Route::get('/', [PembayaranFormsController::class, 'index'])->name('pembayaran.form.index');
    Route::get('/create', [PembayaranFormsController::class, 'create'])->name('pembayaran.form.create');
    Route::post('/', [PembayaranFormsController::class, 'store'])->name('pembayaran.form.store');
    Route::get('/{id}/edit', [PembayaranFormsController::class, 'edit'])->name('pembayaran.form.edit');
    Route::put('/{id}', [PembayaranFormsController::class, 'update'])->name('pembayaran.form.update');
    Route::delete('/{id}', [PembayaranFormsController::class, 'destroy'])->name('pembayaran.form.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/pembayaran/form-link')->group(function () {
    Route::get('/', [PembayaranFormLinksController::class, 'index'])->name('pembayaran.form-link.index');
    Route::get('/create', [PembayaranFormLinksController::class, 'create'])->name('pembayaran.form-link.create');
    Route::post('/', [PembayaranFormLinksController::class, 'store'])->name('pembayaran.form-link.store');
    Route::get('/{id}/edit', [PembayaranFormLinksController::class, 'edit'])->name('pembayaran.form-link.edit');
    Route::put('/{id}', [PembayaranFormLinksController::class, 'update'])->name('pembayaran.form-link.update');
    Route::delete('/{id}', [PembayaranFormLinksController::class, 'destroy'])->name('pembayaran.form-link.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/absensi/attendance')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('absensi.attendance.index');
    Route::get('/create', [AttendanceController::class, 'create'])->name('absensi.attendance.create');
    Route::post('/', [AttendanceController::class, 'store'])->name('absensi.attendance.store');
    Route::get('/{id}/edit', [AttendanceController::class, 'edit'])->name('absensi.attendance.edit');
    Route::put('/{id}', [AttendanceController::class, 'update'])->name('absensi.attendance.update');
    Route::delete('/{id}', [AttendanceController::class, 'destroy'])->name('absensi.attendance.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/absensi/attendance-setting')->group(function () {
    Route::get('/', [AttendanceSettingController::class, 'index'])->name('absensi.attendance-setting.index');
    Route::get('/create', [AttendanceSettingController::class, 'create'])->name('absensi.attendance-setting.create');
    Route::post('/', [AttendanceSettingController::class, 'store'])->name('absensi.attendance-setting.store');
    Route::get('/{id}/edit', [AttendanceSettingController::class, 'edit'])->name('absensi.attendance-setting.edit');
    Route::put('/{id}', [AttendanceSettingController::class, 'update'])->name('absensi.attendance-setting.update');
    Route::delete('/{id}', [AttendanceSettingController::class, 'destroy'])->name('absensi.attendance-setting.destroy');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/absensi/attendance-user-qr-code')->group(function () {
    Route::get('/', [AttendanceUserQrCodeController::class, 'index'])->name('absensi.attendance-user-qr-code.index');
    Route::get('/create', [AttendanceUserQrCodeController::class, 'create'])->name('absensi.attendance-user-qr-code.create');
    Route::post('/', [AttendanceUserQrCodeController::class, 'store'])->name('absensi.attendance-user-qr-code.store');
    Route::get('/{id}/edit', [AttendanceUserQrCodeController::class, 'edit'])->name('absensi.attendance-user-qr-code.edit');
    Route::put('/{id}', [AttendanceUserQrCodeController::class, 'update'])->name('absensi.attendance-user-qr-code.update');
    Route::delete('/{id}', [AttendanceUserQrCodeController::class, 'destroy'])->name('absensi.attendance-user-qr-code.destroy');
    Route::post('/{id}/generate-qr', [AttendanceUserQrCodeController::class, 'generateQr'])->name('absensi.attendance-user-qr-code.generate-qr');
});

Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/absensi/academic-calendar')->group(function () {
    Route::get('/', [AcademicCalendarController::class, 'index'])->name('absensi.academic-calendar.index');
    Route::get('/create', [AcademicCalendarController::class, 'create'])->name('absensi.academic-calendar.create');
    Route::post('/', [AcademicCalendarController::class, 'store'])->name('absensi.academic-calendar.store');
    Route::get('/{id}/edit', [AcademicCalendarController::class, 'edit'])->name('absensi.academic-calendar.edit');
    Route::put('/{id}', [AcademicCalendarController::class, 'update'])->name('absensi.academic-calendar.update');
    Route::delete('/{id}', [AcademicCalendarController::class, 'destroy'])->name('absensi.academic-calendar.destroy');
});


Route::middleware(['auth', 'role:superadmin'])->prefix('dashboard/superadmin/student/student')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('student.student.index');
    Route::get('/create', [StudentController::class, 'create'])->name('student.student.create');
    Route::post('/', [StudentController::class, 'store'])->name('student.student.store');
    Route::get('/{id}', [StudentController::class, 'show'])->name('student.student.show');
    Route::get('/{id}/edit', [StudentController::class, 'edit'])->name('student.student.edit');
    Route::put('/{id}', [StudentController::class, 'update'])->name('student.student.update');
    Route::delete('/{id}', [StudentController::class, 'destroy'])->name('student.student.destroy');
    Route::post('/{id}/add-user', [StudentController::class, 'addUser'])->name('student.student.add-user');
});

Route::get('/dashboard/invitation/register-ulang',  [BackendInvitationController::class, 'RegisterUlangScan'])->name('register-ulang.scan');
Route::post('/dashboard/invitation/register-ulang', [BackendInvitationController::class, 'processScan'])->name('register-ulang.process');

Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::prefix('invitation')->name('invitation.')->group(function () {
        Route::get('/',               [BackendInvitationController::class, 'index'])->name('index');
        Route::get('/create',         [BackendInvitationController::class, 'create'])->name('create');
        Route::post('/',              [BackendInvitationController::class, 'store'])->name('store');
        Route::get('/{invitation}',   [BackendInvitationController::class, 'show'])->name('show');
        Route::get('/{invitation}/edit', [BackendInvitationController::class, 'edit'])->name('edit');
        Route::put('/{invitation}',   [BackendInvitationController::class, 'update'])->name('update');
        Route::delete('/{invitation}',[BackendInvitationController::class, 'destroy'])->name('destroy');
        Route::post('/{invitation}/resend', [BackendInvitationController::class, 'resend'])->name('resend');

        // ============================================================
        // Tambahkan 2 route ini di dalam group dashboard
        // ============================================================

        // Letakkan SEBELUM route /{invitation} agar tidak konflik
        // Route::get('/register-ulang',  [BackendInvitationController::class, 'RegisterUlangScan'])->name('register-ulang.scan');
        // Route::post('/register-ulang', [BackendInvitationController::class, 'processScan'])->name('register-ulang.process');
    });

});
// Halaman "Profile" yang dibuka dari dropdown avatar (header/sidebar). Sengaja
// TIDAK ada parameter {id} di URL manapun di sini — ProfileController selalu
// beroperasi terhadap Auth::user()/$request->user(), jadi tidak mungkin satu
// user mengubah data user lain lewat form ini.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__ . '/auth.php';
