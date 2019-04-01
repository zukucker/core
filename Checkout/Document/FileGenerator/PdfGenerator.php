<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Document\FileGenerator;

use Dompdf\Dompdf;
use Dompdf\Options;
use Shopware\Core\Checkout\Document\DocumentGenerated;

class PdfGenerator implements FileGeneratorInterface
{
    public const FILE_EXTENSION = 'pdf';

    public function supports(): string
    {
        return FileTypes::PDF;
    }

    public function getExtension(): string
    {
        return self::FILE_EXTENSION;
    }

    public function generate(DocumentGenerated $documentGenerated): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setIsHtml5ParserEnabled(true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', $documentGenerated->getPageOrientation());
        $dompdf->loadHtml($documentGenerated->getHtml());

        /*
         * Dompdf creates and destroys a lot of objects. The garbage collector slows the process down by ~50% for
         * PHP <7.3
         */
        $gcEnabledAtStart = gc_enabled();
        if ($gcEnabledAtStart) {
            gc_collect_cycles();
            gc_disable();
        }

        $dompdf->render();

        if ($gcEnabledAtStart) {
            gc_enable();
        }

        return $dompdf->output();
    }
}
