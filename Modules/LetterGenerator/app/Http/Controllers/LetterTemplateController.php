<?php

namespace Modules\LetterGenerator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\LetterGenerator\Models\LetterTemplate;
use Modules\LetterGenerator\Services\PlaceholderService;

/**
 * Letter Template Controller
 *
 * Handles CRUD operations for letter templates used in document generation.
 * Provides functionality to create, view, edit, and delete letter templates
 * with placeholder support for employee data.
 *
 * @author Dev Agent
 */
class LetterTemplateController extends Controller
{
    /**
     * Display a listing of letter templates.
     */
    public function index(): View
    {
        $templates = LetterTemplate::orderBy('created_at', 'desc')->paginate(15);

        return view('lettergenerator::templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new letter template.
     */
    public function create(): View
    {
        $placeholders = PlaceholderService::getGroupedPlaceholders();

        return view('lettergenerator::templates.create', compact('placeholders'));
    }

    /**
     * Store a newly created letter template in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'required|in:en,ar',
            'content' => 'required|string',
        ]);

        LetterTemplate::create($validated);

        return redirect()->route('letter-templates.index')
            ->with('success', 'Letter template created successfully.');
    }

    /**
     * Display the specified letter template.
     */
    public function show(LetterTemplate $letterTemplate): View
    {
        $placeholders = PlaceholderService::getGroupedPlaceholders();

        return view('lettergenerator::templates.show', compact('letterTemplate', 'placeholders'));
    }

    /**
     * Show the form for editing the specified letter template.
     */
    public function edit(LetterTemplate $letterTemplate): View
    {
        $placeholders = PlaceholderService::getGroupedPlaceholders();

        return view('lettergenerator::templates.edit', compact('letterTemplate', 'placeholders'));
    }

    /**
     * Update the specified letter template in storage.
     */
    public function update(Request $request, LetterTemplate $letterTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'required|in:en,ar',
            'content' => 'required|string',
        ]);

        $letterTemplate->update($validated);

        return redirect()->route('letter-templates.index')
            ->with('success', 'Letter template updated successfully.');
    }

    /**
     * Remove the specified letter template from storage.
     */
    public function destroy(LetterTemplate $letterTemplate): RedirectResponse
    {
        $letterTemplate->delete();

        return redirect()->route('letter-templates.index')
            ->with('success', 'Letter template deleted successfully.');
    }
}
