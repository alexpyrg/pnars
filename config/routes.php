<?php

declare(strict_types=1);

use App\Core\Http\Router;
use App\Modules\Accidents\AccidentController;
use App\Modules\Admin\InvitationController;
use App\Modules\Admin\UserController;
use App\Modules\Attachments\AttachmentController;
use App\Modules\Audit\AuditController;
use App\Modules\Auth\AuthController;
use App\Modules\Auth\InvitationAcceptController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Flags\FlagController;
use App\Modules\Roads\RoadController;
use App\Modules\Vehicles\VehicleController;

return static function (Router $router): void {
    $router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
    $router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf']);
    $router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);

    $router->get('/invitation/accept', [InvitationAcceptController::class, 'show'], ['guest']);
    $router->post('/invitation/accept', [InvitationAcceptController::class, 'accept'], ['guest', 'csrf']);

    $router->get('/', [DashboardController::class, 'index'], ['auth']);

    $router->get('/accidents', [AccidentController::class, 'index'], ['auth']);
    $router->get('/accidents/datatables', [AccidentController::class, 'datatable'], ['auth']);
    $router->get('/accidents/create', [AccidentController::class, 'create'], ['auth']);
    $router->post('/accidents', [AccidentController::class, 'store'], ['auth', 'csrf']);
    $router->get('/accidents/{id}', [AccidentController::class, 'show'], ['auth']);
    $router->get('/accidents/{id}/edit', [AccidentController::class, 'edit'], ['auth']);
    $router->post('/accidents/{id}/update', [AccidentController::class, 'update'], ['auth', 'csrf']);
    $router->post('/accidents/{id}/delete', [AccidentController::class, 'destroy'], ['auth', 'csrf']);
    $router->post('/accidents/{id}/status', [AccidentController::class, 'changeStatus'], ['auth', 'csrf']);

    $router->get('/accidents/{accident_id}/roads/create', [RoadController::class, 'create'], ['auth']);
    $router->post('/accidents/{accident_id}/roads', [RoadController::class, 'store'], ['auth', 'csrf']);
    $router->get('/roads/{id}', [RoadController::class, 'show'], ['auth']);
    $router->get('/roads/{id}/edit', [RoadController::class, 'edit'], ['auth']);
    $router->post('/roads/{id}/update', [RoadController::class, 'update'], ['auth', 'csrf']);
    $router->post('/roads/{id}/delete', [RoadController::class, 'destroy'], ['auth', 'csrf']);

    $router->get('/accidents/{accident_id}/vehicles/create', [VehicleController::class, 'create'], ['auth']);
    $router->post('/accidents/{accident_id}/vehicles', [VehicleController::class, 'store'], ['auth', 'csrf']);
    $router->get('/vehicles/{id}', [VehicleController::class, 'show'], ['auth']);
    $router->get('/vehicles/{id}/edit', [VehicleController::class, 'edit'], ['auth']);
    $router->post('/vehicles/{id}/update', [VehicleController::class, 'update'], ['auth', 'csrf']);
    $router->post('/vehicles/{id}/delete', [VehicleController::class, 'destroy'], ['auth', 'csrf']);

    $router->post('/attachments/upload', [AttachmentController::class, 'upload'], ['auth', 'csrf']);
    $router->get('/attachments/{id}/download', [AttachmentController::class, 'download'], ['auth']);
    $router->post('/attachments/{id}/delete', [AttachmentController::class, 'delete'], ['auth', 'csrf']);

    $router->post('/accidents/{accident_id}/flags', [FlagController::class, 'store'], ['auth', 'csrf']);
    $router->post('/flags/{id}/resolve', [FlagController::class, 'resolve'], ['auth', 'csrf']);

    $router->get('/admin/users', [UserController::class, 'index'], ['auth', 'role:administrator']);
    $router->post('/admin/users/{id}/toggle-active', [UserController::class, 'toggleActive'], ['auth', 'role:administrator', 'csrf']);
    $router->get('/admin/invitations', [InvitationController::class, 'index'], ['auth', 'role:administrator']);
    $router->post('/admin/invitations', [InvitationController::class, 'store'], ['auth', 'role:administrator', 'csrf']);
    $router->post('/admin/invitations/{id}/cancel', [InvitationController::class, 'cancel'], ['auth', 'role:administrator', 'csrf']);
    $router->get('/admin/audit-logs', [AuditController::class, 'index'], ['auth', 'role:administrator']);
};
