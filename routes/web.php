<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('pages.login');
    })->name('login');
});

Route::middleware('auth')->group(function () {
    Route::middleware('module:dashboard')->get('/dashboard', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::middleware('module:roles')->get('/users', function () {
            return view('pages.settings.users');
        })->name('users');
        
        Route::middleware('module:roles')->group(function () {
            Route::get('/roles', function () {
                return view('pages.settings.roles.index');
            })->name('roles.index');
            
            Route::get('/roles/create', function () {
                return view('pages.settings.roles.edit', ['role' => null]);
            })->name('roles.create');

            Route::get('/roles/{role}/edit', function (App\Models\Role $role) {
                return view('pages.settings.roles.edit', ['role' => $role]);
            })->name('roles.edit');
        });
        
        Route::middleware('module:master_data')->get('/organization', function () {
            return view('pages.settings.organization');
        })->name('organization');
        
        Route::middleware('module:classification')->get('/classifications', function () {
            return view('pages.settings.classifications');
        })->name('classifications');

        Route::middleware('module:audit')->get('/audit-trail', function () {
            return view('pages.settings.audit-trail');
        })->name('audit-trail');
    });

    Route::prefix('registry')->name('registry.')->group(function () {
        Route::middleware('module:in_out_mail')->get('/incoming', function () {
            return view('pages.registry.incoming.index');
        })->name('incoming.index');
        
        Route::middleware('module:input_incoming')->get('/incoming/create', function () {
            return view('pages.registry.incoming.create');
        })->name('incoming.create');

        Route::middleware('module:input_incoming')->get('/incoming/{document}/edit', function (\App\Models\Document $document) {
            return view('pages.registry.incoming.edit', ['document' => $document]);
        })->name('incoming.edit');

        Route::middleware('module:in_out_mail')->get('/outgoing', function () {
            return view('pages.registry.outgoing.index');
        })->name('outgoing.index');

        Route::middleware('module:input_outgoing')->get('/outgoing/create', function () {
            return view('pages.registry.outgoing.create');
        })->name('outgoing.create');

        Route::middleware('module:internal_memo')->get('/special', function () {
            return view('pages.registry.internal.index');
        })->name('special.index');

        Route::get('/internal', function () {
            return redirect()->route('registry.special.index');
        })->name('internal.index');

        Route::middleware('module:archive_queue')->get('/scan-queue', App\Livewire\Registry\ScanQueue::class)->name('scan-queue');

        Route::middleware('module:input_internal')->get('/special/create', function () {
            return view('pages.registry.internal.create');
        })->name('special.create');

        Route::get('/internal/create', function () {
            return redirect()->route('registry.special.create');
        })->name('internal.create');
    });



    Route::middleware('module:document_borrowing,loan_circulation')->get('/borrowings', App\Livewire\Registry\DocumentLoanIndex::class)->name('borrowings.index');

    Route::middleware('module:cancelled_documents')->get('/canceldoc', App\Livewire\Registry\CancelledDocumentIndex::class)->name('registry.cancelled.index');

    Route::prefix('archive')->name('archive.')->group(function () {
        Route::middleware('module:destruction,approve_destruction')->get('/destructions', function () {
            return view('pages.archive.destructions');
        })->name('destructions');
    });

    Route::get('/document/preview/{version}', function (App\Models\DocumentVersion $version) {
        // Dual-Layer Storage Resolution: Cek NAS storage terlebih dahulu, lalu fallback ke local storage
        $path = Storage::disk('nas')->path($version->file_path);

        if (!file_exists($path)) {
            $fallbackPath = storage_path('app/nas/' . $version->file_path);
            if (!file_exists($fallbackPath)) {
                $fallbackPath = storage_path('app/public/' . $version->file_path);
            }
            if (file_exists($fallbackPath)) {
                $path = $fallbackPath;
            } else {
                abort(404);
            }
        }

        $document = $version->document;
        if (!$document) {
            abort(404, 'Dokumen arsip tidak ditemukan.');
        }

        if (!Auth::user()->hasDocumentPermission('view', $document->sifat ?? 'biasa')) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk melihat pratinjau dokumen dengan sifat ini.');
        }

        $downloadFileName = $document
            ? $document->generateArchiveFileName('pdf', (string) $version->version_number)
            : basename($path);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $downloadFileName . '"'
        ]);
    })->name('document.preview');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});








