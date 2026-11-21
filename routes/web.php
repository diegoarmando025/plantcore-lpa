<?php
use App\Http\Controllers\LpaAuthController;
use App\Http\Controllers\LpaController;
use App\Http\Controllers\LpaTemplateController;
use App\Http\Controllers\LpaCalendarController;
use App\Http\Controllers\LpaBatchController;
use App\Http\Controllers\LpaExportController;
use App\Http\Controllers\LpaSummaryExportController;
use Illuminate\Support\Facades\Route;
Route::redirect('/','/audits');
Route::middleware('guest')->group(function(){
 Route::get('/login',[LpaAuthController::class,'login'])->name('login');
 Route::post('/login',[LpaAuthController::class,'authenticate'])->name('login.store');
});
Route::middleware('auth')->group(function(){
 Route::post('/logout',[LpaAuthController::class,'logout'])->name('logout');
 Route::get('/audits',[LpaController::class,'dashboard'])->name('lpa.dashboard');
 Route::get('/calendar',[LpaCalendarController::class,'index'])->name('lpa.calendar');
 Route::get('/templates',[LpaTemplateController::class,'index'])->name('lpa.templates.index');
 Route::get('/templates/create',[LpaTemplateController::class,'create'])->name('lpa.templates.create');
 Route::post('/templates',[LpaTemplateController::class,'store'])->name('lpa.templates.store');
 Route::get('/templates/{template}',[LpaTemplateController::class,'show'])->name('lpa.templates.show');
 Route::get('/templates/{template}/edit',[LpaTemplateController::class,'edit'])->name('lpa.templates.edit');
 Route::put('/templates/{template}',[LpaTemplateController::class,'update'])->name('lpa.templates.update');
 Route::post('/templates/{template}/toggle',[LpaTemplateController::class,'toggle'])->name('lpa.templates.toggle');
 Route::get('/audits/batch',[LpaBatchController::class,'create'])->name('lpa.batch.create');
 Route::post('/audits/batch',[LpaBatchController::class,'store'])->name('lpa.batch.store');
 Route::get('/exports/audits',[LpaExportController::class,'audits'])->name('lpa.exports.audits');
 Route::get('/exports/summary',LpaSummaryExportController::class)->name('lpa.exports.summary');
 Route::get('/exports/actions',[LpaExportController::class,'actions'])->name('lpa.exports.actions');
 Route::get('/audits/create',[LpaController::class,'create'])->name('lpa.audits.create');
 Route::post('/audits',[LpaController::class,'store'])->name('lpa.audits.store');
 Route::get('/audits/{audit}',[LpaController::class,'show'])->name('lpa.audits.show');
 Route::post('/audits/{audit}/complete',[LpaController::class,'complete'])->name('lpa.audits.complete');
 Route::get('/actions',[LpaController::class,'actions'])->name('lpa.actions');
 Route::post('/actions/{action}/due-date',[LpaController::class,'updateAction'])->name('lpa.actions.update');
 Route::get('/reports',[LpaController::class,'report'])->name('lpa.reports');
 Route::post('/actions/{action}/close',[LpaController::class,'closeAction'])->name('lpa.actions.close');
});
