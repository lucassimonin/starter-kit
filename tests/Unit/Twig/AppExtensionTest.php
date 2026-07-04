<?php

namespace App\Tests\Unit\Twig;

use App\Block\BlockRegistry;
use App\Service\SettingsProvider;
use App\Twig\AppExtension;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class AppExtensionTest extends TestCase
{
    private AppExtension $extension;

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->extension = new AppExtension(
            new BlockRegistry([]),
            new SettingsProvider($em),
            $em,
            new RequestStack(),
            'fr',
        );
    }

    public function testSplitPairsParsesLeftAndRight(): void
    {
        $result = $this->extension->splitPairs("Planche à partager | 14€\nTapas du marché | 8€");

        $this->assertCount(2, $result);
        $this->assertSame(['left' => 'Planche à partager', 'right' => '14€'], $result[0]);
        $this->assertSame(['left' => 'Tapas du marché', 'right' => '8€'], $result[1]);
    }

    public function testSplitPairsWithoutSeparator(): void
    {
        $result = $this->extension->splitPairs('Réservation table');

        $this->assertSame([['left' => 'Réservation table', 'right' => '']], $result);
    }

    public function testSplitPairsIgnoresEmptyLines(): void
    {
        $result = $this->extension->splitPairs("Un | 1\n\n   \nDeux | 2\n");

        $this->assertCount(2, $result);
    }

    public function testSplitPairsKeepsExtraPipesInRightPart(): void
    {
        $result = $this->extension->splitPairs('Citation | Auteur | Ville');

        $this->assertSame('Citation', $result[0]['left']);
        $this->assertSame('Auteur | Ville', $result[0]['right']);
    }

    public function testSplitPairsWithNull(): void
    {
        $this->assertSame([], $this->extension->splitPairs(null));
    }
}
