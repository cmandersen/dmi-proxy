<?php

namespace App\Http\Controllers;

use App\Actions\Docs\GenerateApiDocs;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function __invoke(GenerateApiDocs $docs): View
    {
        return view('docs.api', ['endpoints' => $docs->execute()]);
    }
}
