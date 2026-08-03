<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

if (! function_exists('userMayAccessOfficeBusiness')) {
    function userMayAccessOfficeBusiness(?User $user, string $businessId): bool
    {
        if (! $user || $businessId === '') {
            return false;
        }

        return Business::whereKey($businessId)->exists();
    }
}

if (! function_exists('issueBusinessId')) {
    function issueBusinessId(): string
    {
        $request = request();

        if ($request && $request->filled('business_id') && Auth::check()) {
            $businessId = (string) $request->input('business_id');
            if (Business::whereKey($businessId)->exists()) {
                session(['mainBusinessID' => $businessId]);

                return $businessId;
            }
        }

        if ($request) {
            $routeBusiness = $request->route('business');
            if ($routeBusiness !== null && $routeBusiness !== '') {
                return (string) $routeBusiness;
            }
        }

        $sessionId = session('mainBusinessID');
        if ($sessionId !== null && $sessionId !== '') {
            return (string) $sessionId;
        }

        $default = trim((string) config('services.line.ims.default_business_id'));
        if ($default !== '' && Business::whereKey($default)->exists()) {
            return $default;
        }

        return (string) Business::query()->value('id');
    }
}

if (! function_exists('formatThaiDate')) {
    /**
     * Format a date with Thai month name, e.g. "3 สิงหาคม 2026".
     */
    function formatThaiDate(mixed $date, string $pattern = 'd M Y'): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        try {
            $carbon = $date instanceof \Carbon\CarbonInterface
                ? $date
                : \Carbon\Carbon::parse($date);
        } catch (\Throwable) {
            return '-';
        }

        $months = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม',
        ];

        $replacements = [
            'd' => $carbon->format('d'),
            'j' => (string) $carbon->day,
            'm' => $carbon->format('m'),
            'n' => (string) $carbon->month,
            'Y' => $carbon->format('Y'),
            'y' => $carbon->format('y'),
            'H' => $carbon->format('H'),
            'i' => $carbon->format('i'),
            's' => $carbon->format('s'),
            'M' => $months[(int) $carbon->month] ?? $carbon->format('M'),
            'F' => $months[(int) $carbon->month] ?? $carbon->format('F'),
        ];

        return strtr($pattern, $replacements);
    }
}

if (! function_exists('officeBusinessId')) {
    function officeBusinessId(): ?string
    {
        $request = request();

        if ($request && $request->attributes->has('office_business_id')) {
            return (string) $request->attributes->get('office_business_id');
        }

        if ($request) {
            $routeBusiness = $request->route('business');
            if ($routeBusiness !== null && $routeBusiness !== '') {
                return (string) $routeBusiness;
            }

            if ($request->filled('business_id') && Auth::check()) {
                $bid = (string) $request->input('business_id');
                if (userMayAccessOfficeBusiness(Auth::user(), $bid)) {
                    return $bid;
                }
            }
        }

        $sessionId = session('mainBusinessID');
        if ($sessionId !== null && $sessionId !== '') {
            return (string) $sessionId;
        }

        return Business::query()->value('id');
    }
}

if (! function_exists('officeBusinessRoute')) {
    function officeBusinessRoute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $businessId = officeBusinessId();
        $scopedName = str_starts_with($name, 'office.') ? $name : 'office.'.$name;
        $routes = Route::getRoutes();

        $targetName = $name;
        if (Route::has($scopedName)) {
            $scopedRoute = $routes->getByName($scopedName);
            $scopedNeedsBusiness = $scopedRoute && in_array('business', $scopedRoute->parameterNames(), true);

            if (! $scopedNeedsBusiness || $businessId) {
                $targetName = $scopedName;
            }
        } elseif (str_starts_with($name, 'office.') && Route::has($name)) {
            $targetName = $name;
        }

        $route = $routes->getByName($targetName);

        if (! is_array($parameters)) {
            $allParamNames = $route ? $route->parameterNames() : [];
            $nonBusinessParams = array_values(array_filter($allParamNames, fn ($p) => $p !== 'business'));

            if (count($nonBusinessParams) === 0 && in_array('business', $allParamNames, true)) {
                $parameters = ['business' => $parameters];
            } else {
                $parameters = [($nonBusinessParams[0] ?? 'id') => $parameters];
            }
        }

        if (isset($parameters['business']) && $parameters['business'] !== '') {
            $businessId = (string) $parameters['business'];
        }

        if ($route && in_array('business', $route->parameterNames(), true) && $businessId) {
            $parameters['business'] = $businessId;
        } else {
            unset($parameters['business']);
        }

        return route($targetName, $parameters, $absolute);
    }
}
