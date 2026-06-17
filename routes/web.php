<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\ChatController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\DocumentController;
use App\Http\Controllers\Student\DocumentFolderController;
use App\Http\Controllers\Student\FlashcardController;
use App\Http\Controllers\Student\QuizController;
use App\Http\Controllers\Student\StudyNoteController;
use App\Http\Controllers\Student\SummaryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect(auth()->user()->isAdmin() ? route('admin.dashboard') : route('student.dashboard'))
        : view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:mahasiswa,admin'])->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

Route::middleware(['auth', 'role:mahasiswa'])->group(function () {
    Route::get('dashboard', StudentDashboardController::class)->name('student.dashboard');
    Route::view('panduan', 'student.guide')->name('student.guide');
    Route::delete('documents-selected', [DocumentController::class, 'bulkDestroy'])->name('documents.bulk-destroy');
    Route::resource('documents', DocumentController::class)->except(['edit', 'update']);
    Route::get('folders/{folder}', [DocumentFolderController::class, 'show'])->name('folders.show');
    Route::post('folders/{folder}/documents', [DocumentFolderController::class, 'storeDocuments'])->name('folders.documents.store');
    Route::delete('folders/{folder}', [DocumentFolderController::class, 'destroy'])->name('folders.destroy');
    Route::post('documents/{document}/notes', [StudyNoteController::class, 'storeDocument'])->name('documents.notes.store');
    Route::post('folders/{folder}/notes', [StudyNoteController::class, 'storeFolder'])->name('folders.notes.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::post('documents/{document}/summaries', [SummaryController::class, 'store'])->name('summaries.store');
    Route::post('folders/{folder}/summaries', [SummaryController::class, 'storeFolder'])->name('folders.summaries.store');
    Route::get('summaries/{summary}', [SummaryController::class, 'show'])->name('summaries.show');
    Route::redirect('riwayat-tanya-ai', '/riwayat');
    Route::get('riwayat', [ChatController::class, 'index'])->name('chat.index');
    Route::delete('riwayat-selected', [ChatController::class, 'bulkDestroy'])->name('chat.bulk-destroy');
    Route::post('documents/{document}/tanya', [ChatController::class, 'create'])->name('chat.create');
    Route::post('folders/{folder}/tanya', [ChatController::class, 'createFolder'])->name('folders.chat.create');
    Route::post('documents/{document}/chat', [ChatController::class, 'create'])->name('chat.create.legacy');
    Route::post('folders/{folder}/chat', [ChatController::class, 'createFolder'])->name('folders.chat.create.legacy');
    Route::get('tanya/{session}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('tanya/{session}', [ChatController::class, 'store'])->name('chat.store');
    Route::get('chat/{session}', [ChatController::class, 'show'])->name('chat.show.legacy');
    Route::post('chat/{session}', [ChatController::class, 'store'])->name('chat.store.legacy');
    Route::post('documents/{document}/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::post('folders/{folder}/quizzes', [QuizController::class, 'storeFolder'])->name('folders.quizzes.store');
    Route::get('quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::post('quizzes/{quiz}/attempts', [QuizController::class, 'storeAttempt'])->name('quizzes.attempts.store');
    Route::post('documents/{document}/flashcards', [FlashcardController::class, 'store'])->name('flashcards.store');
    Route::post('folders/{folder}/flashcards', [FlashcardController::class, 'storeFolder'])->name('folders.flashcards.store');
    Route::get('documents/{document}/flashcards', [FlashcardController::class, 'index'])->name('flashcards.index');
    Route::get('folders/{folder}/flashcards', [FlashcardController::class, 'indexFolder'])->name('folders.flashcards.index');
    Route::patch('flashcards/{flashcard}/review', [FlashcardController::class, 'review'])->name('flashcards.review');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('documents', [AdminDocumentController::class, 'index'])->name('documents.index');
    Route::delete('documents/{document}', [AdminDocumentController::class, 'destroy'])->name('documents.destroy');
});
