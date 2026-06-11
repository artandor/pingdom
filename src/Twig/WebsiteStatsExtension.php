<?php

namespace App\Twig;

use App\Entity\Website;
use App\Repository\WebsiteRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WebsiteStatsExtension extends AbstractExtension
{
    public function __construct(private WebsiteRepository $websiteRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('website_stats', $this->getStats(...)),
        ];
    }

    /**
     * Returns aggregate counts matching the logic in index.html.twig.
     *
     * @return array{total: int, ok: int, warn: int, err: int}
     */
    public function getStats(): array
    {
        $websites = $this->websiteRepository->findAll();

        $ok   = 0;
        $warn = 0;
        $err  = 0;

        foreach ($websites as $w) {
            /** @var Website $w */
            $status       = $w->getStatus();
            $redirectOk   = $w->getRedirectionOk();

            if ($status === 200 && $redirectOk) {
                ++$ok;
            } elseif ($status !== null && $status >= 0
                && (in_array($status, [301, 302], true)
                    || ($status === 200 && !$redirectOk))
            ) {
                ++$warn;
            } else {
                ++$err;
            }
        }

        return [
            'total' => count($websites),
            'ok'    => $ok,
            'warn'  => $warn,
            'err'   => $err,
        ];
    }
}
