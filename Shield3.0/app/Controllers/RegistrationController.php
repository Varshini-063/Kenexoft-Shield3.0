<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class RegistrationController extends Controller
{
    public function index(): void
    {
        $this->render('registration/index', [
            'title' => 'Kenexoft SHIELD v3.0 Registration',
        ]);
    }

}
