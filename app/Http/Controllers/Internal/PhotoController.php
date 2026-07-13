<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\QuestCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    /**
     * Streams a completion's private photo. Protected by a temporary signed URL
     * (see NotifyValidationWorkflowJob); the `signed` middleware validates it.
     */
    public function show(Request $request, QuestCompletion $completion): StreamedResponse
    {
        abort_if($completion->photo_path === null, 404, 'Sin foto.');

        $disk = Storage::disk('photos');
        abort_unless($disk->exists($completion->photo_path), 404, 'Foto no encontrada.');

        return $disk->response($completion->photo_path);
    }
}
