<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Group;
use App\Models\School;
use App\Services\PdfGeneratorService;
use Illuminate\Support\Str;

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
            ->flatMap(fn ($s) => $s->groups->flatMap(fn ($g) => $g->children))
            ->filter(fn ($c) => $c instanceof Child)
            ->values();

        $pdfContent = $this->pdfService->generateGdpr($school, $campaign, $children, $withParentNames);

        $suffix = $withParentNames ? 'cu_nume' : 'fara_nume';
        $filename = 'gdpr_consimtamant_' . $suffix . '_' . now()->format('Y-m-d') . '.pdf';

        $disposition = request()->boolean('preview') ? 'inline' : 'attachment';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $filename . '"');
    }

    /**
     * Download GDPR consent form for a single child (one parent/kid).
     */
    public function downloadGdprChild(School $school, Child $child)
    {
        $this->authorizeChildAccess($school, $child);

        $campaign = $school->campaign;
        if (! $campaign) {
            abort(404, 'No campaign assigned.');
        }

        $withParentNames = filter_var(request('with_parent_names', true), FILTER_VALIDATE_BOOLEAN);
        $pdfContent = $this->pdfService->generateGdpr($school, $campaign, [$child], $withParentNames);

        $slug = Str::slug($child->child_full_name . '_' . $child->parent_full_name);
        $filename = 'gdpr_' . $slug . '_' . now()->format('Y-m-d') . '.pdf';

        $disposition = request()->boolean('preview') ? 'inline' : 'attachment';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $filename . '"');
    }

    /**
     * Test: single-child GDPR PDF. Uses same flow as downloadGdprChild (generateGdpr).
     */
    public function downloadGdprChildTest(School $school, Child $child)
    {
        $campaign = $school->campaign;
        if (! $campaign) {
            abort(404, 'No campaign assigned.');
        }
        $this->authorizeChildAccess($school, $child);

        $withParentNames = filter_var(request('with_parent_names', true), FILTER_VALIDATE_BOOLEAN);
        $pdfContent = $this->pdfService->generateGdpr($school, $campaign, [$child], $withParentNames);

        $filename = 'gdpr_test_' . now()->format('Y-m-d') . '.pdf';
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadGroupDistributionTable(School $school, Group $group)
    {
        $this->authorizeGroupAccess($school, $group);

        $result = $this->pdfService->generateGroupDistributionTable($group);
        $filename = 'lista_distributie_' . Str::slug($group->name) . '_' . now()->format('Y-m-d') . '.pdf';

        $disposition = request()->boolean('preview')
            ? 'inline'
            : 'attachment';

        return response($result['content'])
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $filename . '"');
    }

    protected function authorizeGroupAccess(School $school, Group $group): void
    {
        if ($group->structure->school_id !== $school->id) {
            abort(403);
        }
    }

    protected function authorizeChildAccess(School $school, Child $child): void
    {
        $child->load('group.structure');
        if ($child->group->structure->school_id !== $school->id) {
            abort(403);
        }
    }
}
