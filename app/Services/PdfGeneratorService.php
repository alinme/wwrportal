<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\TemplateProcessor;

class PdfGeneratorService
{
    protected function createMpdf(): Mpdf
    {
        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tmpDir,
            'default_font' => 'dejavusans',
        ]);
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
     * Generate distribution table for a single group (child, parent, signature).
     * format: 'pdf' (default) or 'docx'. DOCX uses Word template if present.
     * @return array{content: string, mime: string, extension: string}
     */
    public function generateGroupDistributionTable(Group $group, string $format = 'pdf'): array
    {
        if ($format === 'docx') {
            $templatePath = storage_path('app/templates/distribution_table_template.docx');
            if (file_exists($templatePath)) {
                return [
                    'content' => $this->generateFromWordTemplate($group, $templatePath),
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'extension' => 'docx',
                ];
            }
        }

        return [
            'content' => $this->generateGroupDistributionTablePdf($group),
            'mime' => 'application/pdf',
            'extension' => 'pdf',
        ];
    }

    protected function generateGroupDistributionTablePdf(Group $group): string
    {
        $mpdf = $this->createMpdf();

        $school = $group->structure->school;
        $structure = $group->structure;

        $html = '
        <style>
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid black; padding: 8px; text-align: left; }
            th { background: #f3f4f6; }
        </style>
        <h2>' . htmlspecialchars($school->official_name) . '</h2>
        <p><strong>' . htmlspecialchars($structure->name) . '</strong> – ' . htmlspecialchars($group->name) . ' (' . htmlspecialchars($group->educator_name) . ')</p>
        <table>
            <thead>
                <tr>
                    <th>Nr.</th>
                    <th>Copil</th>
                    <th>Părinte/Tutore</th>
                    <th>Semnătură</th>
                </tr>
            </thead>
            <tbody>';

        $i = 1;
        foreach ($group->children as $child) {
            $html .= '<tr>
                <td>' . $i++ . '</td>
                <td>' . htmlspecialchars($child->child_full_name) . '</td>
                <td>' . htmlspecialchars($child->parent_full_name) . '</td>
                <td></td>
            </tr>';
        }

        for ($j = 0; $j < 10; $j++) {
            $html .= '<tr>
                <td>' . $i++ . '</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>';
        }

        $html .= '</tbody></table>';

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
     * Only placeholders for data we know: school name, structure, group, educator, child name, parent name (optional), facilitator, month_year.
     * No birth date, no address placeholders.
     *
     * @param  iterable<\App\Models\Child>  $children
     */
    public function generateGdpr(School $school, Campaign $campaign, iterable $children, bool $withParentNames = true): string
    {
        $templatePath = storage_path('app/templates/gdpr.html');
        $childrenArray = is_array($children) ? $children : iterator_to_array($children);

        if (! file_exists($templatePath) || empty($childrenArray)) {
            return $this->generateGdprFallback($school, $campaign, $childrenArray, $withParentNames);
        }

        $mpdf = $this->createMpdf();
        $facilitator = $campaign->facilitator_name ?? '';
        $monthYear = $campaign->month_year_suffix ?? '';

        foreach ($childrenArray as $index => $child) {
            $group = $child->group;
            $structure = $group->structure;

            $html = file_get_contents($templatePath);

            $replacements = [
                '${SCHOOL_NAME}' => $school->official_name,
                '${STRUCTURE_NAME}' => $structure->name,
                '${GROUP_NAME}' => $group->name,
                '${EDUCATOR_NAME}' => $group->educator_name ?? '',
                '${CHILD_NAME}' => $child->child_full_name,
                '${PARENT_NAME}' => $withParentNames ? $child->parent_full_name : '_________________________',
                '${FACILITATOR_NAME}' => $facilitator,
                '${MONTH_YEAR_SUFFIX}' => $monthYear,
            ];

            foreach ($replacements as $placeholder => $value) {
                $html = str_replace($placeholder, htmlspecialchars($value), $html);
            }

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
