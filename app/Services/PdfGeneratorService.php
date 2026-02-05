<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Child;
use App\Models\Group;
use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\TemplateProcessor;

class PdfGeneratorService
{
    /** Custom fonts directory: place Cambria.ttf and optional NamesFont.ttf here for GDPR PDFs. */
    protected const FONT_DIR = 'app/fonts';

    /** Return the first font filename that exists in the directory (checks actual filesystem). */
    protected function findFontFile(string $fontDir, array $candidates): ?string
    {
        foreach ($candidates as $name) {
            if (file_exists($fontDir.\DIRECTORY_SEPARATOR.$name)) {
                return $name;
            }
        }

        return null;
    }

    protected function createMpdf(array $options = []): Mpdf
    {
        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $config = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tmpDir,
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_header' => 8,
            'margin_footer' => 8
        ];

        if (! empty($options['gdpr_fonts'])) {
            $config = array_merge($config, $this->getGdprFontConfig());
        }
        if (isset($options['format'])) {
            $config['format'] = $options['format'];
        }

        return new Mpdf($config);
    }

    /**
     * Font config for GDPR PDFs: Cambria for body, optional separate font for parent/child names.
     * Place TTF files in storage/app/fonts/:
     *   - Cambria.ttf (and optionally Cambria-Bold.ttf) for main text
     *   - NamesFont.ttf for parent/child names only (any name you use in the template as font-family)
     */
    protected function getGdprFontConfig(): array
    {
        $defaultConfig = (new \Mpdf\Config\ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'] ?? [];
        $defaultFontConfig = (new \Mpdf\Config\FontVariables)->getDefaults();
        $fontdata = $defaultFontConfig['fontdata'] ?? [];
        $defaultFont = 'Cambria';

        $fontDir = storage_path(self::FONT_DIR);
        if (is_dir($fontDir)) {
            $fontDirs = array_merge($fontDirs, [$fontDir]);

            $cambriaR = $this->findFontFile($fontDir, ['Cambria.ttf']);
            if ($cambriaR) {
                $fontdata['cambria'] = [
                    'R' => $this->findFontFile($fontDir, ['Cambria.ttf']),
                ];
                // Required when a font file is a TrueType Collection (TTC). Only set for styles that use the same file as R.
                $ttcIds = ['R' => 0];
                $fontdata['Cambria']['TTCfontID'] = $ttcIds;
                $defaultFont = 'Cambria';
            }

            $namesR = $this->findFontFile($fontDir, ['NamesFont.ttf', 'namesfont.ttf']);
            if ($namesR) {
                $fontdata['namesfont'] = [
                    'R' => $namesR,
                    'B' => $this->findFontFile($fontDir, ['NamesFont-Bold.ttf', 'namesfont-bold.ttf']) ?: $namesR,
                ];
            }
        }

        return [
            'fontDir' => $fontDirs,
            'fontdata' => $fontdata,
            'default_font' => $defaultFont,
        ];
    }

    public function generateContract(School $school, Campaign $campaign)
    {
        $mpdf = $this->createMpdf();

        $templatePath = storage_path('app/templates/contract_template.pdf');

        if (file_exists($templatePath)) {
            $pagecount = $mpdf->SetSourceFile($templatePath);
            $tplId = $mpdf->ImportPage(1);
            $mpdf->UseTemplate($tplId);
        } else {
            // Fallback if no template: simple text
            $mpdf->WriteHTML('<h1>Contract de Parteneriat</h1>');
        }

        // Overlay text - adjust coordinates based on real template
        $mpdf->SetFontSize(12);
        $mpdf->WriteText(50, 50, $school->official_name);
        $mpdf->WriteText(50, 60, $campaign->name);

        return $mpdf->Output('', 'S');
    }

    /**
     * Save PDF to R2 and return temporary signed download URL, or null if R2 not configured.
     */
    public function saveToR2AndGetUrl(string $pdfContent, string $filename): ?string
    {
        if (! config('filesystems.disks.r2.bucket')) {
            return null;
        }

        $path = 'pdfs/'.date('Y-m-d').'/'.$filename;
        Storage::disk('r2')->put($path, $pdfContent, 'private');

        return Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(15));
    }

    public function generateAnnex(School $school, Campaign $campaign)
    {
        $mpdf = $this->createMpdf();

        $templatePath = storage_path('app/templates/annex_template.pdf');

        if (file_exists($templatePath)) {
            $pagecount = $mpdf->SetSourceFile($templatePath);
            $tplId = $mpdf->ImportPage(1);
            $mpdf->UseTemplate($tplId);
        } else {
            $mpdf->WriteHTML('<h1>Anexa 1 - Lista Beneficiari</h1>');
        }

        // Add table of children
        $html = '
        <style>
            table { width: 100%; border-collapse: collapse; margin-top: 100px; }
            th, td { border: 1px solid black; padding: 5px; text-align: left; }
        </style>
        <table>
            <thead>
                <tr>
                    <th>Nume Copil</th>
                    <th>Parinte</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($school->groups()->with('children')->get() as $group) {
            foreach ($group->children as $child) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($child->child_full_name) . '</td>
                    <td>' . htmlspecialchars($child->parent_full_name) . '</td>
                </tr>';
            }
        }

        $html .= '</tbody></table>';

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    /**
     * Generate distribution table for a single group (child, parent, signature) as PDF.
     * @return array{content: string, mime: string, extension: string}
     */
    public function generateGroupDistributionTable(Group $group): array
    {
        return [
            'content' => $this->generateGroupDistributionTablePdf($group),
            'mime' => 'application/pdf',
            'extension' => 'pdf',
        ];
    }

    protected function generateGroupDistributionTablePdf(Group $group): string
    {
        $mpdf = $this->createMpdf(['format' => 'A4-L']);

        $school = $group->structure->school;
        $structure = $group->structure;
        $campaign = $school->campaign;

        $facilitator = $campaign->facilitator_name ?? $group->educator_name ?? '';
        $distribution_date = $campaign->month_year_suffix ?? now()->format('d.m.Y');
        // Schools table: state = Județ (county), city = Oraș/Comună; structures have address only
        $state_county = $school->state ?? '';
        $city = $school->city ?? '';
        $address = $structure->address ?? $school->address ?? '';

        $rows = [];
        $i = 1;
        foreach ($group->children as $child) {
            $rows[] = [
                'number' => $i++,
                'child_name' => $child->child_full_name ?? '',
                'parent_name' => $child->parent_full_name ?? '',
            ];
        }
        $emptyRows = max(0, 10 - count($rows));
        for ($j = 0; $j < $emptyRows; $j++) {
            $rows[] = [
                'number' => $i++,
                'child_name' => '',
                'parent_name' => '',
            ];
        }
        if (empty($rows)) {
            $rows[] = ['number' => 1, 'child_name' => '', 'parent_name' => ''];
            $i = 2;
        }
        // Always add one full page of empty rows for future handwritten additions (A4-L ~20 rows per page)
        $emptyRowsPerPage = 20;
        for ($j = 0; $j < $emptyRowsPerPage; $j++) {
            $rows[] = [
                'number' => $i++,
                'child_name' => '',
                'parent_name' => '',
            ];
        }

        $html = View::make('list-template', [
            'school_name' => $school->official_name ?? '',
            'structure_name' => ($structure->same_location_as_school ?? false) ? '' : ($structure->name ?? ''),
            'group_name' => $group->name ?? '',
            'educator_name' => $group->educator_name ?? '',
            'state_county' => $state_county,
            'city' => $city,
            'address' => $address,
            'facilitator' => $facilitator,
            'distribution_date' => $distribution_date,
            'rows' => $rows,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    /**
     * Generate distribution table from Word template.
     * Template placeholders: ${SCHOOL_NAME}, ${STRUCTURE_NAME}, ${GROUP_NAME}, ${EDUCATOR_NAME}
     * Use a table with rows. Clone row for each child. Placeholders in row: ${CHILD_NAME}, ${PARENT_NAME}
     * Or use ${CHILDREN_ROWS} - we'll replace with XML. PHPWord template cloning is complex;
     * for simplicity we use a basic approach: replace single-row placeholders and clone.
     */
    protected function generateFromWordTemplate(Group $group, string $templatePath): string
    {
        $template = new TemplateProcessor($templatePath);

        $template->setValue('SCHOOL_NAME', $group->structure->school->official_name);
        $template->setValue('STRUCTURE_NAME', $group->structure->name);
        $template->setValue('GROUP_NAME', $group->name);
        $template->setValue('EDUCATOR_NAME', $group->educator_name);

        $children = $group->children;
        $childCount = $children->count();
        $count = max(1, $childCount + 10); // All children + 10 empty rows
        $template->cloneRow('CHILD_NAME', $count);
        foreach ($children as $i => $child) {
            $template->setValue('CHILD_NAME#' . ($i + 1), $child->child_full_name);
            $template->setValue('PARENT_NAME#' . ($i + 1), $child->parent_full_name);
        }
        for ($i = $childCount + 1; $i <= $count; $i++) {
            $template->setValue('CHILD_NAME#' . $i, '');
            $template->setValue('PARENT_NAME#' . $i, '');
        }

        $tmpFile = storage_path('app/tmp/distribution_' . $group->id . '_' . time() . '.docx');
        $template->saveAs($tmpFile);

        $content = file_get_contents($tmpFile);
        @unlink($tmpFile);

        return $content;
    }

    /**
     * Generate GDPR consent form(s). One page per child.
     * Uses resources/views/gdpr-template.blade.php. QR image from storage/app/templates/qr.png if present.
     *
     * @param  iterable<\App\Models\Child>  $children
     */
    public function generateGdpr(School $school, Campaign $campaign, iterable $children, bool $withParentNames = true): string
    {
        $childrenArray = is_array($children) ? $children : iterator_to_array($children);

        // Only process actual Child models. If $children was a string (e.g. wrong arg), iterating gives one char per "child" = millions of pages.
        $childrenArray = array_values(array_filter($childrenArray, fn ($c) => $c instanceof Child));
        $maxPages = 5000;
        if (count($childrenArray) > $maxPages) {
            $childrenArray = array_slice($childrenArray, 0, $maxPages);
        }

        if (empty($childrenArray)) {
            return $this->generateGdprFallback($school, $campaign, $childrenArray, $withParentNames);
        }

        $mpdf = $this->createMpdf(['gdpr_fonts' => true]);

        // QR image: same folder as former template (storage/app/templates/qr.png), embedded as base64 for mPDF
        $qrPath = storage_path('app/templates/qr.png');
        $qrSrc = '';
        if (is_file($qrPath)) {
            $qrSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));
        }

        foreach ($childrenArray as $index => $child) {
            $group = $child->group;
            $structure = $group->structure;

            $parentName = $withParentNames ? ($child->parent_full_name ?? '') : '_________________________';
            $parentLocality = $child->parent_locality ?? '_________________________';
            $parentCounty = $child->parent_county ?? '_________________________';
            $parentBirthDate = $child->parent_birth_date
                ? $child->parent_birth_date->format('d.m.Y')
                : '_________________________';
            $childBirthDate = $child->child_birth_date
                ? $child->child_birth_date->format('d.m.Y')
                : '_________________________';

            $html = View::make('gdpr-template', [
                'parent_name' => $parentName,
                'parent_locality' => $parentLocality,
                'parent_county' => $parentCounty,
                'parent_birth_date' => $parentBirthDate,
                'child_name' => $child->child_full_name,
                'child_birth_date' => $childBirthDate,
                'school_name' => $school->official_name,
                'structure_name' => $structure->name,
                'group_name' => $group->name,
                'qr_src' => $qrSrc,
            ])->render();

            $mpdf->WriteHTML($html);
            if ($index < count($childrenArray) - 1) {
                $mpdf->AddPage();
            }
        }

        return $mpdf->Output('', 'S');
    }

    /**
     * @param  array<\App\Models\Child>  $children
     */
    protected function generateGdprFallback(School $school, Campaign $campaign, array $children, bool $withParentNames): string
    {
        $mpdf = $this->createMpdf();

        if (empty($children)) {
            $mpdf->WriteHTML('<p>No children to generate GDPR forms.</p>');
            return $mpdf->Output('', 'S');
        }

        foreach ($children as $child) {
            $group = $child->group;
            $structure = $group->structure;
            $parentName = $withParentNames ? $child->parent_full_name : '_________________________';

            $html = '<h2>CONSIMȚĂMÂNT GDPR</h2>
            <p>Școala: ' . htmlspecialchars($school->official_name) . '</p>
            <p>Structura: ' . htmlspecialchars($structure->name) . '</p>
            <p>Grupa: ' . htmlspecialchars($group->name) . '</p>
            <p>Educator: ' . htmlspecialchars($group->educator_name ?? '') . '</p>
            <p>Copil: ' . htmlspecialchars($child->child_full_name) . '</p>
            <p>Părinte/Tutore: ' . htmlspecialchars($parentName) . '</p>
            <p>Facilitator: ' . htmlspecialchars($campaign->facilitator_name ?? '') . ' ' . htmlspecialchars($campaign->month_year_suffix ?? '') . '</p>
            <p style="margin-top:40px">Semnătură: _________________________ Data: _________________________</p>';

            $mpdf->WriteHTML($html);
            $mpdf->AddPage();
        }

        $mpdf->DeletePage($mpdf->GetNumPages()); // Remove extra blank page

        return $mpdf->Output('', 'S');
    }
}
