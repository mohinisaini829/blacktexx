<?php declare(strict_types=1);

namespace zenit\PlatformNotificationBar\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class GetControllerInfo
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getCurrentControllerInfo(): array
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $currentController = $currentRequest->attributes->get('_controller');

        if (
            empty($currentController)
        ) {
            return [
                '1' => '',
                '2' => '',
            ];
        }

        $matches = [];
        preg_match('/Controller\\\\(\w+)Controller::?(\w+)$/', (string) $currentController, $matches);

        if (
            empty($matches)
        ) {
            $controllerName = explode('\\', (string) $currentController);
            $controllerName = end($controllerName);
            $controllerAction = explode('::', $controllerName);

            if (
                !empty($controllerAction)
            ) {
                $controllerAction = end($controllerAction);
            }

            if (
                !empty($controllerAction)
                && !empty($controllerName)
                && ($pos = strrpos($controllerName, 'Controller::' . $controllerAction))
            ) {
                $controllerName = substr($controllerName, 0, $pos);
            }

            $matches = [
                '1' => $controllerName ?? '',
                '2' => $controllerAction ?? '',
            ];
        }

        return $matches;
    }

    public function getCurrentController(): string
    {
        return $this->getCurrentControllerInfo()[1] . '.' . $this->getCurrentControllerInfo()[2];
    }

    public function getCurrentControllerName(): string
    {
        return $this->getCurrentControllerInfo()[1];
    }

    public function getCurrentControllerAction(): string
    {
        return $this->getCurrentControllerInfo()[2];
    }
}
