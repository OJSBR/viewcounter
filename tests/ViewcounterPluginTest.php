<?php

/**
 * @file plugins/generic/viewcounter/tests/ViewcounterPluginTest.php
 *
 * Copyright (c) 2024-2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ViewcounterPluginTest
 *
 * @brief The badge markup, the counts cache and the site level.
 */

namespace APP\plugins\generic\viewcounter\tests;

use APP\plugins\generic\viewcounter\ViewcounterPlugin;
use APP\plugins\generic\viewcounter\ViewcounterSettingsForm;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\tests\PKPTestCase;

#[CoversClass(ViewcounterPlugin::class)]
#[CoversClass(ViewcounterSettingsForm::class)]
class ViewcounterPluginTest extends PKPTestCase
{
    /** A plugin whose counts come from a fixed table instead of the statistics service. */
    private function plugin(array $counts = []): ViewcounterPlugin
    {
        return new class ($counts) extends ViewcounterPlugin {
            public int $queries = 0;

            public function __construct(private array $fixed)
            {
                parent::__construct();
            }

            protected function sumByType(int $contextId, array $submissionIds, array $assocTypes): array
            {
                $this->queries++;
                $key = in_array(\APP\core\Application::ASSOC_TYPE_SUBMISSION, $assocTypes, true) ? 'views' : 'downloads';
                return array_intersect_key(array_map(fn ($c) => $c[$key], $this->fixed), array_flip($submissionIds));
            }
        };
    }

    private function submission(int $id): \APP\submission\Submission
    {
        $submission = new \APP\submission\Submission();
        $submission->setId($id);
        $submission->setData('contextId', 1);
        return $submission;
    }

    protected function tearDown(): void
    {
        foreach ([900001, 900002, 900003] as $id) {
            \Illuminate\Support\Facades\Cache::forget(ViewcounterPlugin::cacheKey($id));
        }
        parent::tearDown();
    }

    public function testTheBadgeCarriesBothCountsWithAccessibleLabels(): void
    {
        $html = $this->plugin([900001 => ['views' => 12, 'downloads' => 3]])->renderBadge($this->submission(900001), 'summary');

        $this->assertStringContainsString('class="viewcounter-stats viewcounter-stats--summary" data-vc-place="summary"', $html);
        $this->assertMatchesRegularExpression('/aria-label="[^"]+: 12"/', $html);
        $this->assertMatchesRegularExpression('/aria-label="[^"]+: 3"/', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function testAnUnknownPlaceFallsBackToTheSummaryAndTheThemeFunctionDoesNotRelocate(): void
    {
        $plugin = $this->plugin([900001 => ['views' => 1, 'downloads' => 1]]);

        $this->assertStringContainsString('viewcounter-stats--summary', $plugin->renderBadge($this->submission(900001), '"><script>'));
        $this->assertStringNotContainsString('data-vc-place', $plugin->smartyViewcounterStats(['submission' => $this->submission(900001)], null));
        $this->assertSame('', $plugin->smartyViewcounterStats([], null));
    }

    public function testAListIsCountedWithOneQueryPerMetricAndThenServedFromTheCache(): void
    {
        $plugin = $this->plugin([900001 => ['views' => 5, 'downloads' => 1], 900002 => ['views' => 7, 'downloads' => 2]]);
        $plugin->primeCache([$this->submission(900001), $this->submission(900002), 'not a submission']);

        $this->assertSame(2, $plugin->queries, 'One grouped query per metric type.');
        $this->assertSame(['views' => 7, 'downloads' => 2], $plugin->getCounts($this->submission(900002)));
        $this->assertSame(2, $plugin->queries, 'The page must not query again per article.');

        $second = $this->plugin([900001 => ['views' => 99, 'downloads' => 99]]);
        $this->assertSame(['views' => 5, 'downloads' => 1], $second->getCounts($this->submission(900001)), 'The next request reads the cache.');
        $this->assertSame(0, $second->queries);
    }

    public function testASubmissionWithoutMetricsCountsZero(): void
    {
        $this->assertSame(['views' => 0, 'downloads' => 0], $this->plugin()->getCounts($this->submission(900003)));
    }

    public function testTheSiteLevelHasNoSettingsToOpen(): void
    {
        $request = new class () {
            public function getContext()
            {
                return null;
            }

            public function getUserVar($name)
            {
                return $name === 'verb' ? 'settings' : null;
            }

            public function getRouter()
            {
                throw new \RuntimeException('The site level must not build a settings URL.');
            }
        };
        $plugin = new class () extends ViewcounterPlugin {
            public function getEnabled($contextId = null)
            {
                return true;
            }
        };

        $this->assertSame([], array_filter($plugin->getActions($request, []), fn ($action) => $action->getId() === 'settings'));
        $this->expectExceptionMessage('Unhandled management action!');
        $plugin->manage([], $request);
    }

    public function testNoGlobalConstantOnlyDefinedOutsideStrictMode(): void
    {
        // STYLE_SEQUENCE_LAST and friends are only defined globally when PKP_STRICT_MODE is off.
        $this->assertSame(0, preg_match('/(?<![:\w])STYLE_SEQUENCE_\w+/', (string) file_get_contents(dirname(__DIR__) . '/ViewcounterPlugin.php')));
    }
}
