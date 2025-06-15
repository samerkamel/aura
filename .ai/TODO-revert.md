# Debug Log

## Temp Debug for Story 3.2

### Issue: AttendanceCalculationService returning 0.0 instead of calculated hours

**File**: `/Users/SamerKamel/Root/aura/Modules/Payroll/app/Services/AttendanceCalculationService.php`
**Issue**: Service logic has problems with data fetching and calculation
**Status**: Fixed multiple diffInMinutes parameter order issues, added debug logging to calculateLatePenalty method

**Problems identified**:

1. Service returns 0.0 for all calculations
2. Database constraint violations in tests (email uniqueness, foreign key issues) - FIXED
3. Added debug logging to understand data flow
4. **FIXED**: Duration calculation had wrong parameter order in diffInMinutes - should be $signInLog->timestamp->diffInMinutes($signOutLog->timestamp)
5. **FIXED**: Late penalty calculation had wrong parameter order in diffInMinutes - should be $flexibleStartTime->diffInMinutes($signInTime)
6. **INVESTIGATING**: Service applying 60-minute penalty instead of expected 30-minute penalty for 15 minutes late

**Expected Outcome**: Service should return correct calculated hours based on attendance logs and rules
