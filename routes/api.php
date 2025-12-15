<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AUTH ROUTES
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'App\Http\Controllers\Api\V1\Account\AuthController@login')->name('login');
    Route::put('logout', 'App\Http\Controllers\Api\V1\Account\AuthController@logout');
    Route::post('refresh', 'App\Http\Controllers\Api\V1\Account\AuthController@refresh');
    Route::get('me', 'App\Http\Controllers\Api\V1\Account\AuthController@me');
});

Route::post('account/forgot-password', 'App\Http\Controllers\Api\V1\Account\ForgotPasswordController@forgotPassword');

Route::post('account/reset-password', 'App\Http\Controllers\Api\V1\Account\ResetPasswordController@resetPassword');

Route::middleware('auth:sanctum')->group(function () {

    Route::group(['prefix' => 'users'], function () {

        Route::get('/', 'App\Http\Controllers\Api\V1\User\UserController@index');

        // store user
        Route::post('/', 'App\Http\Controllers\Api\V1\User\UserController@store');

        // group by {id}
        Route::group(['prefix' => '{id}'], function () {

            Route::get('/', 'App\Http\Controllers\Api\V1\User\UserController@show');
            // update user
            Route::put('/', 'App\Http\Controllers\Api\V1\User\UserController@update');

            // delete user
            Route::delete('/', 'App\Http\Controllers\Api\V1\User\UserController@destroy');
        });
    });

    // FEATURES
    Route::group(['prefix' => 'features'], function () {

        Route::get('/', 'App\Http\Controllers\Api\V1\System\FeatureController@index');

        Route::group(['prefix' => '{id}'], function () {

            Route::get('/', 'App\Http\Controllers\Api\V1\System\FeatureController@show');
            Route::put('/', 'App\Http\Controllers\Api\V1\System\FeatureController@update');
        });
    });

    // ROLES
    Route::group(['prefix' => 'roles'], function () {

        Route::get('/', 'App\Http\Controllers\Api\V1\Role\RoleController@index');

        Route::post('/', 'App\Http\Controllers\Api\V1\Role\RoleController@store');

        Route::group(['prefix' => '{id}'], function () {

            Route::get('/', 'App\Http\Controllers\Api\V1\Role\RoleController@show');
            Route::put('/', 'App\Http\Controllers\Api\V1\Role\RoleController@update');

            Route::delete('/', 'App\Http\Controllers\Api\V1\Role\RoleController@destroy');

            // PERMISSIONS
            Route::group(['prefix' => 'permissions'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Role\Permission\PermissionController@index');

                Route::post('/', 'App\Http\Controllers\Api\V1\Role\Permission\PermissionController@store');

                Route::group(['prefix' => '{permissionId}'], function () {

                    Route::get('/', 'App\Http\Controllers\Api\V1\Role\Permission\PermissionController@show');
                    Route::put('/', 'App\Http\Controllers\Api\V1\Role\Permission\PermissionController@update');

                    Route::delete('/', 'App\Http\Controllers\Api\V1\Role\Permission\PermissionController@destroy');
                });
            });

            // USERS
            Route::group(['prefix' => 'users'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Role\User\UserController@index');
            });
        });
    });


    Route::group(['prefix' => 'account'], function () {

        Route::post('verify', 'App\Http\Controllers\Api\V1\Account\VerifyAccountController@verify');

        Route::put('verify/resend', 'App\Http\Controllers\Api\V1\Account\VerifyAccountController@resend');



        Route::put('update-password', 'App\Http\Controllers\Api\V1\Account\AccountController@updatePassword');

        Route::put('update-account', 'App\Http\Controllers\Api\V1\Account\AccountController@updateAccount');

        Route::put('update-account-email', 'App\Http\Controllers\Api\V1\Account\AccountController@updateAccountEmail');

        Route::put('update-account-image', 'App\Http\Controllers\Api\V1\Account\AccountController@updateImage');

        // /api/account/auth/logs
        Route::get('auth/logs', 'App\Http\Controllers\Api\V1\Account\AccountController@getAccountAuthLogs');
    });



    // Company
    Route::group(['prefix' => 'company'], function () {

        // TYPES
        Route::group(['prefix' => 'types'], function () {
            // types
            Route::get('/', 'App\Http\Controllers\Api\V1\Company\Type\CompanyTypeController@index');

            Route::post('/', 'App\Http\Controllers\Api\V1\Company\Type\CompanyTypeController@store');

            Route::group(['prefix' => '{companyTypeId}'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Company\Type\CompanyTypeController@show');

                Route::put('/', 'App\Http\Controllers\Api\V1\Company\Type\CompanyTypeController@update');

                Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Type\CompanyTypeController@destroy');
            });
        });

        // DESIGNATION
        Route::group(['prefix' => 'designations'], function () {

            Route::get('/', 'App\Http\Controllers\Api\V1\Company\Designation\CompanyDesignationController@index');

            Route::post('/', 'App\Http\Controllers\Api\V1\Company\Designation\CompanyDesignationController@store');

            Route::group(['prefix' => '{schoolDesignationId}'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Company\Designation\CompanyDesignationController@show');

                Route::put('/', 'App\Http\Controllers\Api\V1\Company\Designation\CompanyDesignationController@update');

                Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Designation\CompanyDesignationController@destroy');
            });
        });
    });

    // COMPANIES
    Route::group(['prefix' => 'companies'], function () {

        Route::get('/', 'App\Http\Controllers\Api\V1\Company\CompanyController@index');

        Route::post('/', 'App\Http\Controllers\Api\V1\Company\CompanyController@store');

        Route::group(['prefix' => '{companyId}'], function () {

            Route::get('/', 'App\Http\Controllers\Api\V1\Company\CompanyController@show');

            Route::put('/', 'App\Http\Controllers\Api\V1\Company\CompanyController@update');

            Route::delete('/', 'App\Http\Controllers\Api\V1\Company\CompanyController@destroy');

            // ADMINISTRATORS
            Route::group(['prefix' => 'administrators'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Company\Administrator\CompanyAdministratorController@index');

                Route::post('/', 'App\Http\Controllers\Api\V1\Company\Administrator\CompanyAdministratorController@store');

                Route::group(['prefix' => '{schoolAdministratorId}'], function () {

                    Route::get('/', 'App\Http\Controllers\Api\V1\Company\Administrator\CompanyAdministratorController@show');

                    Route::put('/', 'App\Http\Controllers\Api\V1\Company\Administrator\CompanyAdministratorController@update');

                    Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Administrator\CompanyAdministratorController@destroy');
                });
            });


            // SUBSCRIPTIONS
            Route::group(['prefix' => 'subscriptions'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionController@index');
                Route::post('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionController@store');

                Route::group(['prefix' => '{companySubscriptionId}'], function () {

                    Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionController@show');
                    Route::put('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionController@update');
                    Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionController@destroy');

                    // BENEFITS
                    Route::group(['prefix' => 'benefits'], function () {
                        Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionBenefitController@index');
                        Route::post('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionBenefitController@store');
                        Route::group(['prefix' => '{companySubscriptionBenefitId}'], function () {
                            Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionBenefitController@show');
                            Route::put('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionBenefitController@update');
                            Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanySubscriptionBenefitController@destroy');
                        });
                    });


                    // MEMBERS
                    Route::prefix('members')->group(function () {

                        Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanyMemberSubscriptionController@index');
                        Route::post('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanyMemberSubscriptionController@store');

                        Route::post('/bulk-update', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanyMemberSubscriptionController@bulkUpdate');

                        Route::prefix('{cMSubscriptionId}')->group(function () {
                            Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanyMemberSubscriptionController@show');
                            Route::put('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanyMemberSubscriptionController@update');
                            Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CompanyMemberSubscriptionController@destroy');
                            // /api/companies/{companyId}/subscriptions/{companySubscriptionId}/members/{companyMemberSubscriptionId}/check-ins/{id}
                            Route::prefix('check-ins')->group(function () {
                                Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CheckIn\CompanyMemberSubscriptionCheckInController@index');
                                Route::post('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CheckIn\CompanyMemberSubscriptionCheckInController@store');
                                Route::group(['prefix' => '{cMSubscriptionCheckInId}'], function () {
                                    Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CheckIn\CompanyMemberSubscriptionCheckInController@show');
                                    Route::put('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CheckIn\CompanyMemberSubscriptionCheckInController@update');
                                    Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Subscription\CheckIn\CompanyMemberSubscriptionCheckInController@destroy');
                                });
                            });
                        });
                    });

                    // TRANSACTIONS
                    Route::prefix('transactions')->group(function () {

                        Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\Transaction\CompanySubscriptionTransactionController@index');
                        Route::post('/', 'App\Http\Controllers\Api\V1\Company\Subscription\Transaction\CompanySubscriptionTransactionController@store');
                        // SUMMARY
                        Route::get('/summary', 'App\Http\Controllers\Api\V1\Company\Subscription\Transaction\CompanySubscriptionTransactionController@summary');
                        // bulk-create
                        Route::post('/bulk-create', 'App\Http\Controllers\Api\V1\Company\Subscription\Transaction\CompanySubscriptionTransactionController@bulkCreate');

                        Route::group(['prefix' => '{companySubscriptionTransactionId}'], function () {
                            Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\Transaction\CompanySubscriptionTransactionController@show');
                            Route::put('/', 'App\Http\Controllers\Api\V1\Company\Subscription\Transaction\CompanySubscriptionTransactionController@update');
                            Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Subscription\Transaction\CompanySubscriptionTransactionController@destroy');

                            // status
                            Route::put('/status', 'App\Http\Controllers\Api\V1\Company\Subscription\Transaction\CompanySubscriptionTransactionController@updateStatus');
                        });
                    });
                });
            });


            // DOCUMENTS
            Route::group(['prefix' => 'documents'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Company\Document\CompanyDocumentController@index');
                Route::post('/', 'App\Http\Controllers\Api\V1\Company\Document\CompanyDocumentController@store');
                Route::get('/groups', 'App\Http\Controllers\Api\V1\Company\Document\CompanyDocumentController@getGroups');

                Route::post('/bulk-upload', 'App\Http\Controllers\Api\V1\Company\Document\CompanyDocumentController@bulkUpload');

                Route::group(['prefix' => '{companyDocumentId}'], function () {
                    Route::get('/', 'App\Http\Controllers\Api\V1\Company\Document\CompanyDocumentController@show');
                    Route::put('/', 'App\Http\Controllers\Api\V1\Company\Document\CompanyDocumentController@update');
                    Route::delete('/', 'App\Http\Controllers\Api\V1\Company\Document\CompanyDocumentController@destroy');
                });
            });
        });
    });


    // LOCATION
    Route::group(['prefix' => 'location'], function () {
        Route::get('countries', 'App\Http\Controllers\Api\V1\Location\LocationController@getCountries');

        // get country
        Route::get('countries/{id}', 'App\Http\Controllers\Api\V1\Location\LocationController@getCountry');

        Route::get('provinces', 'App\Http\Controllers\Api\V1\Location\LocationController@getProvinces');

        // get province
        Route::get('/provinces', 'App\Http\Controllers\Api\V1\Location\LocationController@getProvinces');
        Route::get('/provinces/{id}', 'App\Http\Controllers\Api\V1\Location\LocationController@getProvince');

        // Districts
        Route::get('/districts', 'App\Http\Controllers\Api\V1\Location\LocationController@getDistricts');
        Route::get('/district/{id}', 'App\Http\Controllers\Api\V1\Location\LocationController@getDistrict');

        // Sectors
        Route::get('/sectors', 'App\Http\Controllers\Api\V1\Location\LocationController@getSectors');
        Route::get('/sector/{id}', 'App\Http\Controllers\Api\V1\Location\LocationController@getSector');

        // Cells
        Route::get('/cells', 'App\Http\Controllers\Api\V1\Location\LocationController@getCells');
        Route::get('/cell/{id}', 'App\Http\Controllers\Api\V1\Location\LocationController@getCell');

        // Villages
        Route::get('/villages', 'App\Http\Controllers\Api\V1\Location\LocationController@getVillages');
        Route::get('/village/{id}', 'App\Http\Controllers\Api\V1\Location\LocationController@getVillage');

        // Countries
        Route::get('/countries', 'App\Http\Controllers\Api\V1\Location\LocationController@getCountries');
        Route::get('/country/{id}', 'App\Http\Controllers\Api\V1\Location\LocationController@getCountry');

        // Get Districts by Province
        Route::get('/provinces/{province_id}/districts', 'App\Http\Controllers\Api\V1\Location\LocationController@getDistrictsByProvince');

        // Get Sectors by District
        Route::get('/districts/{district_id}/sectors', 'App\Http\Controllers\Api\V1\Location\LocationController@getSectorsByDistrict');

        // Get Sectors by Province
        Route::get('/provinces/{province_id}/sectors', 'App\Http\Controllers\Api\V1\Location\LocationController@getSectorsByProvince');

        // Get Cells by Sector
        Route::get('/sectors/{sector_id}/cells', 'App\Http\Controllers\Api\V1\Location\LocationController@getCellsBySector');

        // Get Cells by District
        Route::get('/districts/{district_id}/cells', 'App\Http\Controllers\Api\V1\Location\LocationController@getCellsByDistrict');

        // Get Villages by Sector
        Route::get('/sectors/{sector_id}/villages', 'App\Http\Controllers\Api\V1\Location\LocationController@getVillagesBySector');

        // Get Villages by Cell
        Route::get('/cells/{cell_id}/villages', 'App\Http\Controllers\Api\V1\Location\LocationController@getVillagesByCell');

        // Get Villages by District
        Route::get('/districts/{district_id}/villages', 'App\Http\Controllers\Api\V1\Location\LocationController@getVillagesByDistrict');

        // Get Villages by Province
        Route::get('/provinces/{province_id}/villages', 'App\Http\Controllers\Api\V1\Location\LocationController@getVillagesByProvince');
    });

    // MEMBERS
    Route::group(['prefix' => 'members'], function () {

        Route::get('/', 'App\Http\Controllers\Api\V1\Member\MemberController@index');

        Route::post('/', 'App\Http\Controllers\Api\V1\Member\MemberController@store');

        Route::group(['prefix' => '{memberId}'], function () {

            Route::get('/', 'App\Http\Controllers\Api\V1\Member\MemberController@show');

            Route::put('/', 'App\Http\Controllers\Api\V1\Member\MemberController@update');

            Route::delete('/', 'App\Http\Controllers\Api\V1\Member\MemberController@destroy');


            // current
            Route::get('/current', 'App\Http\Controllers\Api\V1\Member\Subscription\MemberSubscriptionController@currentSubscription');

            Route::prefix('subscriptions')->group(function () {
                Route::get('/', 'App\Http\Controllers\Api\V1\Member\Subscription\MemberSubscriptionController@index');
                Route::post('/', 'App\Http\Controllers\Api\V1\Member\Subscription\MemberSubscriptionController@store');

                //  Status
                Route::put('/status', 'App\Http\Controllers\Api\V1\Member\Subscription\MemberSubscriptionController@updateStatus');
                // active
                Route::get('/active', 'App\Http\Controllers\Api\V1\Member\Subscription\MemberSubscriptionController@activeSubscriptions');
                Route::prefix('{memberSubscriptionId}')->group(function () {
                    Route::get('/', 'App\Http\Controllers\Api\V1\Member\Subscription\MemberSubscriptionController@show');
                    Route::put('/', 'App\Http\Controllers\Api\V1\Member\Subscription\MemberSubscriptionController@update');
                    Route::delete('/', 'App\Http\Controllers\Api\V1\Member\Subscription\MemberSubscriptionController@destroy');

                    // Transactions
                    Route::prefix('transactions')->group(function () {
                        Route::get('/', 'App\Http\Controllers\Api\V1\Member\Subscription\Transaction\MemberSubscriptionTransactionController@index');
                        Route::post('/', 'App\Http\Controllers\Api\V1\Member\Subscription\Transaction\MemberSubscriptionTransactionController@store');

                        // SUMMARY
                        Route::get('/summary', 'App\Http\Controllers\Api\V1\Member\Subscription\Transaction\MemberSubscriptionTransactionController@summary');
                        // bulk-create
                        Route::post('/bulk-create', 'App\Http\Controllers\Api\V1\Member\Subscription\Transaction\MemberSubscriptionTransactionController@bulkCreate');

                        Route::prefix('{memberSubscriptionTransactionId}')->group(function () {
                            Route::get('/', 'App\Http\Controllers\Api\V1\Member\Subscription\Transaction\MemberSubscriptionTransactionController@show');
                            Route::put('/', 'App\Http\Controllers\Api\V1\Member\Subscription\Transaction\MemberSubscriptionTransactionController@update');
                            Route::delete('/', 'App\Http\Controllers\Api\V1\Member\Subscription\Transaction\MemberSubscriptionTransactionController@destroy');

                            // status
                            Route::put('/status', 'App\Http\Controllers\Api\V1\Member\Subscription\Transaction\MemberSubscriptionTransactionController@updateStatus');
                        });
                    });

                    // check-ins
                    Route::prefix('check-ins')->group(function () {
                        Route::get('/', 'App\Http\Controllers\Api\V1\Member\Subscription\CheckIn\MemberSubscriptionCheckInController@index');
                        Route::post('/', 'App\Http\Controllers\Api\V1\Member\Subscription\CheckIn\MemberSubscriptionCheckInController@store');
                        Route::get('/summary/daily', 'App\Http\Controllers\Api\V1\Member\Subscription\CheckIn\MemberSubscriptionCheckInController@summaryDaily');
                        Route::prefix('{memberSubscriptionCheckInId}')->group(function () {
                            Route::get('/', 'App\Http\Controllers\Api\V1\Member\Subscription\CheckIn\MemberSubscriptionCheckInController@show');
                            Route::put('/', 'App\Http\Controllers\Api\V1\Member\Subscription\CheckIn\MemberSubscriptionCheckInController@update');
                            Route::delete('/', 'App\Http\Controllers\Api\V1\Member\Subscription\CheckIn\MemberSubscriptionCheckInController@destroy');
                        });
                    });
                });
            });
        });
    });

    // UTILS
    Route::group(['prefix' => 'utils'], function () {
        // BRANCHES
        Route::group(['prefix' => 'branches'], function () {
            Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Branch\BranchController@index');

            Route::post('/', 'App\Http\Controllers\Api\V1\Utils\Branch\BranchController@store');

            Route::group(['prefix' => '{brachId}'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Branch\BranchController@show');

                Route::put('/', 'App\Http\Controllers\Api\V1\Utils\Branch\BranchController@update');

                Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\Branch\BranchController@destroy');
            });
        });

        // Duration Types
        Route::group(['prefix' => 'duration-types'], function () {
            Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Duration\DurationTypeController@index');

            Route::post('/', 'App\Http\Controllers\Api\V1\Utils\Duration\DurationTypeController@store');

            Route::group(['prefix' => '{durationTypeId}'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Duration\DurationTypeController@show');

                Route::put('/', 'App\Http\Controllers\Api\V1\Utils\Duration\DurationTypeController@update');

                Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\Duration\DurationTypeController@destroy');
            });
        });

        // benefits
        Route::group(['prefix' => 'benefits'], function () {
            Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Benefit\BenefitController@index');
            Route::post('/', 'App\Http\Controllers\Api\V1\Utils\Benefit\BenefitController@store');
            Route::group(['prefix' => '{benefitId}'], function () {
                Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Benefit\BenefitController@show');
                Route::put('/', 'App\Http\Controllers\Api\V1\Utils\Benefit\BenefitController@update');
                Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\Benefit\BenefitController@destroy');
            });
        });


        // Plans
        Route::group(['prefix' => 'plans'], function () {
            Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanController@index');

            Route::post('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanController@store');

            Route::group(['prefix' => '{planId}'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanController@show');

                Route::put('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanController@update');

                Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanController@destroy');

                //  benefits
                Route::group(['prefix' => 'benefits'], function () {
                    Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanBenefitController@index');
                    Route::post('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanBenefitController@store');
                    Route::group(['prefix' => '{benefitId}'], function () {
                        Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanBenefitController@show');
                        Route::put('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanBenefitController@update');
                        Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\Plan\PlanBenefitController@destroy');
                    });
                });
            });
        });


        // Currences
        Route::group(['prefix' => 'currencies'], function () {
            Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Currency\CurrencyController@index');

            Route::post('/', 'App\Http\Controllers\Api\V1\Utils\Currency\CurrencyController@store');

            Route::group(['prefix' => '{currencyId}'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Currency\CurrencyController@show');

                Route::put('/', 'App\Http\Controllers\Api\V1\Utils\Currency\CurrencyController@update');

                Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\Currency\CurrencyController@destroy');
            });
        });


        // Billing types
        Route::group(['prefix' => 'billing-types'], function () {
            Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Billing\BillingTypeController@index');

            Route::post('/', 'App\Http\Controllers\Api\V1\Utils\Billing\BillingTypeController@store');

            Route::group(['prefix' => '{billingTypeId}'], function () {

                Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Billing\BillingTypeController@show');

                Route::put('/', 'App\Http\Controllers\Api\V1\Utils\Billing\BillingTypeController@update');

                Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\Billing\BillingTypeController@destroy');
            });
        });

        // PAYMENT METHODS
        Route::group(['prefix' => 'payment-methods'], function () {
            Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Payment\PaymentMethodController@index');
            Route::post('/', 'App\Http\Controllers\Api\V1\Utils\Payment\PaymentMethodController@store');
            Route::group(['prefix' => '{paymentMethodId}'], function () {
                Route::get('/', 'App\Http\Controllers\Api\V1\Utils\Payment\PaymentMethodController@show');
                Route::put('/', 'App\Http\Controllers\Api\V1\Utils\Payment\PaymentMethodController@update');
                Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\Payment\PaymentMethodController@destroy');
            });
        });

        // check-in-methods
        Route::group(['prefix' => 'check-in-methods'], function () {
            Route::get('/', 'App\Http\Controllers\Api\V1\Utils\CheckIn\CheckInMethodController@index');
            Route::post('/', 'App\Http\Controllers\Api\V1\Utils\CheckIn\CheckInMethodController@store');
            Route::group(['prefix' => '{checkInMethodId}'], function () {
                Route::get('/', 'App\Http\Controllers\Api\V1\Utils\CheckIn\CheckInMethodController@show');
                Route::put('/', 'App\Http\Controllers\Api\V1\Utils\CheckIn\CheckInMethodController@update');
                Route::delete('/', 'App\Http\Controllers\Api\V1\Utils\CheckIn\CheckInMethodController@destroy');
            });
        });
    });

    // group by billing
    Route::group(['prefix' => 'billing'], function () {

        // Company Subscription Billing Routes
        Route::group(['prefix' => 'company-subscriptions'], function () {
            // static routes first
            Route::get('summary/status', 'App\Http\Controllers\Api\V1\Company\Subscription\Billing\CompanySubscriptionBillingController@statusSummary');
            Route::get('expiring-soon', 'App\Http\Controllers\Api\V1\Company\Subscription\Billing\CompanySubscriptionBillingController@expiringSoon');
            // dynamic route last
            Route::get('{companySubscriptionId}', 'App\Http\Controllers\Api\V1\Company\Subscription\Billing\CompanySubscriptionBillingController@show');
            Route::get('/', 'App\Http\Controllers\Api\V1\Company\Subscription\Billing\CompanySubscriptionBillingController@index');
        });

        // Member Subscription Billing Routes
        Route::group(['prefix' => 'member-subscriptions'], function () {
            // static routes first
            Route::get('summary/status', 'App\Http\Controllers\Api\V1\Member\Subscription\Billing\MemberBillingController@statusSummary');
            Route::get('expiring-soon', 'App\Http\Controllers\Api\V1\Member\Subscription\Billing\MemberBillingController@expiringSoon');
            Route::get('summary/daily-registrations', 'App\Http\Controllers\Api\V1\Member\Subscription\Billing\MemberBillingController@dailyRegistrations');
            Route::get('active-by-branch', 'App\Http\Controllers\Api\V1\Member\Subscription\Billing\MemberBillingController@activeByBranch');
            // dynamic route last
            Route::get('{id}', 'App\Http\Controllers\Api\V1\Member\Subscription\Billing\MemberBillingController@show');
            Route::get('/', 'App\Http\Controllers\Api\V1\Member\Subscription\Billing\MemberBillingController@index');
        });
    });
});
