<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class DashboardController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $user = $this->auth()->user();

        $response->view('dashboard/index', [
            'title' => 'Πίνακας Ελέγχου',
            'user' => $user,
        ]);
    }
}
