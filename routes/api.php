<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\LeaveController;
use App\Http\Controllers\API\HolidayController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\ProjectTypeController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\OverviewController;
use App\Http\Controllers\API\LeaveTypeController;
use App\Http\Controllers\API\DailyReportController;
use App\Http\Controllers\API\PasswordController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\API\ForgotPasswordController;


use App\Models\Attendance;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// header('Access-Control-Allow-Origin: *');

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('ensureauth:api')->group( function () {
	Route::get('user/list', [UserController::class, 'index']);
	Route::get('role/list', [UserController::class, 'rolelist']);
	Route::post('user/add', [UserController::class, 'create']);
	Route::get('user/edit/{id}', [UserController::class, 'store']);
	Route::post('user/update/{id}', [UserController::class, 'update']);
	Route::get('user/delete/{id}', [UserController::class, 'destroy']);
	Route::get('/employeerole', [UserController::class, 'employeerole']);

	Route::get('/employee', [EmployeeController::class, 'index']);
	Route::post('/addemployee', [EmployeeController::class, 'addemployee']);
	Route::get('/editemployee/{id}', [EmployeeController::class, 'edit']);
	Route::post('/employeeupdate/{id}', [EmployeeController::class, 'update']);
	Route::get('/employeedelete/{id}', [EmployeeController::class, 'destroy']);
	Route::post('/profileupdate/{id}', [EmployeeController::class, 'profileupdate']);
	Route::get('/admin/{id}', [EmployeeController::class, 'admin']);
	Route::post('/calidigteam', [EmployeeController::class, 'calidigteam']);


	Route::get('/skills', [EmployeeController::class, 'skills']);
	Route::post('/skillsfilter', [EmployeeController::class, 'skillsfilter']);

	Route::get('/teamlist', [EmployeeController::class, 'teamlist']);
	Route::get('/employeelist', [EmployeeController::class, 'employeelist']);

	Route::get('/prediction', [EmployeeController::class, 'prediction']);
	Route::get('/projectchart', [EmployeeController::class, 'projectchart']);


	Route::get('/client', [ClientController::class, 'index']);
	Route::post('/addclient', [ClientController::class, 'addclient']);
	Route::get('/editclient/{id}', [ClientController::class, 'edit']);
	Route::post('/clientupdate/{id}', [ClientController::class, 'update']);
	Route::get('/clientdelete/{id}', [ClientController::class, 'destroy']);


	Route::get('/leave', [LeaveController::class, 'index']);
	Route::post('/addleave', [LeaveController::class, 'addleave']);
	Route::get('leavedelete/{id}', [LeaveController::class, 'destroy']);
	Route::get('/editleave/{id}', [LeaveController::class, 'edit']);
	Route::post('/leaveupdate/{id}', [LeaveController::class, 'update']);
	Route::post('/leavefilter', [LeaveController::class, 'leavefilter']);


	Route::get('/employeeleaves/{id}', [LeaveController::class, 'employeeleaves']);

	Route::get('guidelines', [LeaveController::class, 'guidelines']);
	Route::get('/editguideline/{id}', [LeaveController::class, 'editguideline']);
	Route::post('guidelineupdate/{id}', [LeaveController::class, 'guidelineupdate']);


	Route::get('/holiday', [HolidayController::class, 'index']);
	Route::post('/addholiday', [HolidayController::class, 'addholiday']);
	Route::get('holidaydelete/{id}', [HolidayController::class, 'destroy']);
	Route::get('/editholiday/{id}', [HolidayController::class, 'edit']);
	Route::post('/holidayupdate/{id}', [HolidayController::class, 'update']);

	Route::get('/holidaycalender', [HolidayController::class, 'holidaycalender']);


	Route::get('/project_type', [ProjectTypeController::class, 'index']);
	Route::post('/addproject_type', [ProjectTypeController::class, 'project_type']);
	Route::get('project_type_delete/{id}', [ProjectTypeController::class, 'destroy']);
	Route::get('/edit_project_type/{id}', [ProjectTypeController::class, 'edit']);
	Route::post('/project_type_update/{id}', [ProjectTypeController::class, 'update']);


	Route::get('/leave_type', [LeaveTypeController::class, 'index']);
	Route::post('/addleave_type', [LeaveTypeController::class, 'leave_type']);
	Route::get('leave_type_delete/{id}', [LeaveTypeController::class, 'destroy']);
	Route::get('/edit_leave_type/{id}', [LeaveTypeController::class, 'edit']);
	Route::post('/leave_type_update/{id}', [LeaveTypeController::class, 'update']);


	Route::post('/addproject', [ProjectController::class, 'addproject']);
	Route::get('/project', [ProjectController::class, 'index']);
	Route::get('projectdelete/{id}', [ProjectController::class, 'destroy']);
	Route::get('/editproject/{id}', [ProjectController::class, 'edit']);
	Route::post('/projectupdate/{id}', [ProjectController::class, 'update']);

	Route::get('/empvalue', [ProjectController::class, 'empvalue']);

	Route::get('/employeeproject/{id}', [ProjectController::class, 'employeeproject']);
	Route::get('/clientproject/{id}', [ProjectController::class, 'clientproject']);

	Route::post('/document/{id}', [ProjectController::class, 'document']);

	Route::get('/report', [ReportController::class, 'index']);

	Route::post('/addreport', [ReportController::class, 'addreport']);
	Route::get('/editreport/{id}', [ReportController::class, 'edit']);
	Route::post('/reportupdate/{id}', [ReportController::class, 'update']);
	Route::get('reportdelete/{id}', [ReportController::class, 'destroy']);

	Route::get('/dashboard', [DashboardController::class, 'index']);
	Route::get('/team/list', [EmployeeController::class, 'userteamlist']);

	Route::get('/overview/{id}', [OverviewController::class, 'overview']);
	Route::post('/addoverview', [OverviewController::class, 'addoverview']);
	Route::post('/document_overview/{id}', [OverviewController::class, 'document_overview']);
	Route::get('overviewdelete/{id}', [OverviewController::class, 'destroy']);
	Route::get('/editoverview/{id}', [OverviewController::class, 'edit']);
	Route::post('/overviewupdate/{id}', [OverviewController::class, 'update']);

	Route::get('/overview_detail/{id}', [OverviewController::class, 'overview_detail']);

    // defects list
    Route::get('/overviewDefectsList', [overviewController::class, 'listAllDefects']);

    // add defects
    Route::post('/addDefect', [OverviewController::class, 'addDefect']);

    // client/employee respective Projects List
    Route::get('/listRespectiveProjects', [OverviewController::class, 'listRespectiveProjects']);

    // notify employee regarding leave status
    Route::post('/notifyLeaveStatus', [LeaveController::class, 'notifyLeaveStatus']);

    // notify defect changes
    Route::post('/notifyDefect', [OverviewController::class, 'notifyDefectChanges']);

    // update defect notification
    Route::post('/updateNotifyDefect', [OverviewController::class, 'updateDefectNotification']);

	// Route::get('/dsr', [DailyReportController::class, 'index']);
	Route::post('/add_dsr', [DailyReportController::class, 'add_dsr']);
	Route::get('dsr_delete/{id}', [DailyReportController::class, 'destroy']);
	Route::get('/edit_dsr/{id}', [DailyReportController::class, 'edit']);
	Route::post('/dsr_update/{id}', [DailyReportController::class, 'update']);

	Route::post('/dsrfilter', [DailyReportController::class, 'dsrfilter']);
	Route::get('/project_select', [DailyReportController::class, 'project_select']);

    // list all projects
    Route::get('/listProjects', [DailyReportController::class, 'listProjects']);

    // approve DSR status
    Route::post('/approve_dsr', [DailyReportController::class, 'isDSRapprovedByAdmin']);

    //list respective dsr
    Route::get('/dsr', [DailyReportController::class, 'listDSR']);

    //get project by id
    Route::get('/getProjectById/{id}', [ProjectController::class, 'getProjectById']);

	Route::get('/newtask/{id}', [OverviewController::class, 'newtask']);
	Route::get('/issuetask/{id}', [OverviewController::class, 'issuetask']);
	Route::get('/reissuetask/{id}', [OverviewController::class, 'reissuetask']);
	Route::get('/complete/{id}', [OverviewController::class, 'complete']);
	Route::post('/updatetab', [OverviewController::class, 'updatetab']);

	Route::post('/addcomment', [OverviewController::class, 'addcomment']);
	Route::get('/commentlist/{id}', [OverviewController::class, 'commentlist']);

	Route::get('/notification/{id}', [OverviewController::class, 'notification']);
	Route::get('/allnotification/{id}', [OverviewController::class, 'all_notification']);
	Route::get('deletenotification/{id}', [OverviewController::class, 'deletenotification']);
	Route::get('/notificationstatus', [OverviewController::class, 'notificationstatus']);

	Route::post('/addleavebalance', [LeaveController::class, 'addleavebalance']);
	Route::get('/leavebalance', [LeaveController::class, 'leavebalance']);
	Route::get('leavebalancedelete/{id}', [LeaveController::class, 'leavebalancedelete']);
	Route::get('/editleavebalance/{id}', [LeaveController::class, 'editleavebalance']);
	Route::post('/leavebalanceupdate/{id}', [LeaveController::class, 'leavebalanceupdate']);

	Route::post('/leavestatus', [LeaveController::class, 'leavestatus']);

	Route::post('/password', [PasswordController::class, 'store']);
	Route::get('/defectlist', [OverviewController::class, 'defectlist']);

	Route::get('/attendance', [AttendanceController::class, 'index']);
	Route::get('/attendancechart', [AttendanceController::class, 'attendancechart']);

    Route::get('/checkLeave/{data}/{uid}', [LeaveController::class, 'checkTodaysLeave']);
    Route::get('/attendancePercentage', [AttendanceController::class, 'getWorkingEmployees']);

    Route::post('/addSalary', [AttendanceController::class, 'addSalary']);
    Route::post('/updateSalary', [AttendanceController::class, 'updateSalaryByEmpId']);

    Route::get('/todaysattendance', [AttendanceController::class, 'todaysAttendance']);
});
Route::post('/uploadimage', [OverviewController::class, 'uploadImage']);


Route::get('/get-forget-password', [ForgotPasswordController::class, 'showForgetPasswordForm'])->name('forget.password.get');

Route::post('/post-forget-password', [ForgotPasswordController::class, 'submitForgetPasswordForm'])->name('forget.password.post'); 

Route::get('/get-reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('reset.password.get');

Route::post('/post-reset-password', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');

Route::post('/clear/route', [ForgotPasswordController::class, 'clearRoute']);

