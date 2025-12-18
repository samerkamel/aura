<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Attendance\Models\PublicHoliday;
use Carbon\Carbon;

/**
 * PublicHolidayController
 *
 * Manages CRUD operations for public holidays
 * Used by admins to define holidays that affect attendance calculations
 *
 * @author Dev Agent
 */
class PublicHolidayController extends Controller
{
    /**
     * Display a listing of public holidays for the current year
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $year = $request->get('year', now()->year);

        $holidays = PublicHoliday::forYear($year)
            ->orderBy('date')
            ->get();

        return view('attendance::public-holidays.index', compact('holidays', 'year'));
    }

    /**
     * Store a newly created public holiday in storage
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Determine if this is a date range or single date
        $isDateRange = $request->boolean('is_date_range', false);

        if ($isDateRange) {
            // Validate date range inputs
            $request->validate([
                'name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ], [
                'name.required' => 'Holiday name is required.',
                'start_date.required' => 'Start date is required.',
                'end_date.required' => 'End date is required.',
                'end_date.after_or_equal' => 'End date must be same as or after start date.'
            ]);

            return $this->createHolidayRange($request);
        } else {
            // Validate single date input
            $request->validate([
                'name' => 'required|string|max:255',
                'date' => 'required|date'
            ], [
                'name.required' => 'Holiday name is required.',
                'date.required' => 'Holiday date is required.'
            ]);

            return $this->createSingleHoliday($request);
        }
    }

    /**
     * Create a single holiday entry
     *
     * @param Request $request
     * @return RedirectResponse
     */
    private function createSingleHoliday(Request $request): RedirectResponse
    {
        // Check if a holiday already exists on this date
        $existingHoliday = PublicHoliday::where('date', $request->date)->first();
        if ($existingHoliday) {
            return back()->withErrors(['date' => 'A holiday already exists on this date.'])->withInput();
        }

        PublicHoliday::create([
            'name' => $request->name,
            'date' => $request->date
        ]);

        return redirect()->route('attendance.public-holidays.index')
            ->with('success', 'Public holiday added successfully.');
    }

    /**
     * Create multiple holiday entries for a date range
     *
     * @param Request $request
     * @return RedirectResponse
     */
    private function createHolidayRange(Request $request): RedirectResponse
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $holidayName = $request->name;

        // Check for existing holidays in the range
        $existingHolidays = PublicHoliday::whereBetween('date', [$startDate, $endDate])->get();
        if ($existingHolidays->count() > 0) {
            $conflictDates = $existingHolidays->pluck('date')->map(function ($date) {
                return $date->format('M d, Y');
            })->join(', ');

            return back()->withErrors([
                'start_date' => "Holidays already exist on: {$conflictDates}"
            ])->withInput();
        }

        // Create holidays for each date in the range
        $createdCount = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            PublicHoliday::create([
                'name' => $holidayName,
                'date' => $currentDate->format('Y-m-d')
            ]);

            $createdCount++;
            $currentDate->addDay();
        }

        $message = $createdCount === 1
            ? 'Public holiday added successfully.'
            : "Public holidays added successfully ({$createdCount} days).";

        return redirect()->route('attendance.public-holidays.index')
            ->with('success', $message);
    }

    /**
     * Update the specified public holiday in storage
     *
     * @param Request $request
     * @param PublicHoliday $publicHoliday
     * @return RedirectResponse
     */
    public function update(Request $request, PublicHoliday $publicHoliday): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date'
        ], [
            'name.required' => 'Holiday name is required.',
            'date.required' => 'Holiday date is required.'
        ]);

        // Check if another holiday already exists on this date (excluding current)
        $existingHoliday = PublicHoliday::where('date', $request->date)
            ->where('id', '!=', $publicHoliday->id)
            ->first();

        if ($existingHoliday) {
            return back()->withErrors(['edit_date' => 'Another holiday already exists on this date.'])->withInput();
        }

        $publicHoliday->update([
            'name' => $request->name,
            'date' => $request->date
        ]);

        $year = Carbon::parse($request->date)->year;

        return redirect()->route('attendance.public-holidays.index', ['year' => $year])
            ->with('success', 'Public holiday updated successfully.');
    }

    /**
     * Remove the specified public holiday from storage
     *
     * @param PublicHoliday $publicHoliday
     * @return RedirectResponse
     * @throws \Exception
     */
    public function destroy(PublicHoliday $publicHoliday): RedirectResponse
    {
        $publicHoliday->delete();

        return redirect()->route('attendance.public-holidays.index')
            ->with('success', 'Public holiday deleted successfully.');
    }
}
