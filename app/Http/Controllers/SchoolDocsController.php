<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\School;
use App\Services\PdfGeneratorService;

class SchoolDocsController extends Controller
{
    public function __construct(protected PdfGeneratorService $pdfService) {}

    public function downloadContract(School $school)
    {
        $campaign = $school->campaign;

        if (! $campaign) {
            abort(404, 'No campaign assigned.');
        }

        $pdfContent = $this->pdfService->generateContract($school, $campaign);

        $url = $this->pdfService->saveToR2AndGetUrl(
            $pdfContent,
            'contract_'.$school->id.'_'.now()->format('Y-m-d').'.pdf'
        );

        if ($url) {
            return redirect($url);
        }

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="contract.pdf"');
    }

    public function downloadAnnex(School $school)
    {
        $campaign = $school->campaign;

        if (! $campaign) {
            abort(404, 'No campaign assigned.');
        }

        $pdfContent = $this->pdfService->generateAnnex($school, $campaign);

        $url = $this->pdfService->saveToR2AndGetUrl(
            $pdfContent,
            'anexa_beneficiari_'.$school->id.'_'.now()->format('Y-m-d').'.pdf'
        );

        if ($url) {
            return redirect($url);
        }

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="anexa_beneficiari.pdf"');
    }

    public function downloadGdpr(School $school)
    {
        $campaign = $school->campaign;

        if (! $campaign) {
            abort(404, 'No campaign assigned.');
        }

        $withParentNames = filter_var(request('with_parent_names', true), FILTER_VALIDATE_BOOLEAN);
        $children = $school->structures()
            ->with('groups.children')
            ->get()
            ->flatMap(fn ($s) => $s->groups->flatMap(fn ($g) => $g->children));

        $pdfContent = $this->pdfService->generateGdpr($school, $campaign, $children, $withParentNames);

        $suffix = $withParentNames ? 'cu_nume' : 'fara_nume';
        $filename = 'gdpr_consimtamant_' . $suffix . '_' . now()->format('Y-m-d') . '.pdf';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadGroupDistributionTable(School $school, Group $group)
    {
        $this->authorizeGroupAccess($school, $group);

        $format = request('format', 'pdf');
        $result = $this->pdfService->generateGroupDistributionTable($group, $format);

        $filename = 'lista_distributie_' . \Illuminate\Support\Str::slug($group->name) . '_' . now()->format('Y-m-d') . '.' . $result['extension'];

        return response($result['content'])
            ->header('Content-Type', $result['mime'])
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    protected function authorizeGroupAccess(School $school, Group $group): void
    {
        if ($group->structure->school_id !== $school->id) {
            abort(403);
        }
    }
}
