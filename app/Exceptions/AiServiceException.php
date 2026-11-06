<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

class AiServiceException extends RuntimeException
{
    public function render(Request $request)
    {
        $message = 'AI belum bisa memproses permintaanmu. Coba lagi beberapa saat nanti';

        return $request->expectsJson()
            ? response()->json(['message' => $message], 503)
            : response()->view('errors.layout', [
                'code' => '503', 'title' => 'Permintaan belum berhasil', 'message' => $message,
            ], 503);
    }
}
