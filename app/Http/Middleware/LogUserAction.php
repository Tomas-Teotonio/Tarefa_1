<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogUserAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldLog($request)) {
            return $response;
        }

        try {
            ActivityLog::create([
                'date' => now()->toDateString(),
                'time' => now()->format('H:i:s'),
                'user_id' => Auth::id(),
                'module' => $this->getModule($request),
                'object_id' => $this->getObjectId($request),
                'alteration' => $this->getAlteration($request),
                'ip' => $request->ip(),
                'browser' => substr((string) $request->userAgent(), 0, 1000),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        if (!Auth::check()) {
            return false;
        }

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        if ($request->routeIs('admin.logs.*')) {
            return false;
        }

        return true;
    }

    private function getModule(Request $request): string
    {
        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $request->segment(1) ?? 'Aplicação';
        }

        return match (true) {
            str_starts_with($routeName, 'register') => 'Registo',
            str_starts_with($routeName, 'login') => 'Login',
            str_starts_with($routeName, 'logout') => 'Logout',
            str_starts_with($routeName, 'books') => 'Livros',
            str_starts_with($routeName, 'google.books') => 'Google Books',
            str_starts_with($routeName, 'requests') => 'Requisições',
            str_starts_with($routeName, 'cart') => 'Carrinho',
            str_starts_with($routeName, 'checkout') => 'Checkout',
            str_starts_with($routeName, 'admin.orders') => 'Encomendas',
            str_starts_with($routeName, 'admin.reviews') => 'Reviews',
            str_starts_with($routeName, 'admin.users') => 'Utilizadores',
            str_starts_with($routeName, 'admin.logs') => 'Logs',
            str_starts_with($routeName, 'authors') => 'Autores',
            str_starts_with($routeName, 'publishers') => 'Editoras',
            default => $routeName,
        };
    }

    private function getObjectId(Request $request): ?string
    {
        $parameters = $request->route()?->parameters() ?? [];

        foreach ($parameters as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getKey')) {
                return (string) $parameter->getKey();
            }

            if (is_string($parameter) || is_numeric($parameter)) {
                return (string) $parameter;
            }
        }

        $routeName = $request->route()?->getName();

        if ($routeName === 'register.store' && Auth::check()) {
            return (string) Auth::id();
        }

        if ($routeName === 'login' && Auth::check()) {
            return (string) Auth::id();
        }

        return null;
    }

    private function getAlteration(Request $request): string
    {
        $routeName = $request->route()?->getName() ?? 'sem rota';

        return sprintf(
            '%s %s | %s',
            $request->method(),
            $routeName,
            $request->path()
        );
    }
}