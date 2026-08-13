# F04 Golden Scheduler - V9 Shared Host (PHP Backtracking)

## Purpose
Suggest conflict-free combos based on remaining courses, passed, credit limit, preferences

## Inputs
remainingCourses filtered not in StudentPassedCourse passed, passedCourses list, creditLimit based on declared status, preferences freeDays maxGapHours preferProfessors preferMorning stored UserPreferences, alreadySelectedSpecs

## Algorithm PHP Backtracking (Not brute permutation)
```
function generateGolden(remaining, passed, creditLimit, prefs, alreadySelected):
  filtered = remaining filter not passed
  grouped = groupByCourse filtered (course_id -> [specIds])
  sortedCourses = grouped sort by specs.length ASC MRV
  bestCombos = [], currentCombo = alreadySelected, currentCredits = sum credits, startTime now, timeout 5000ms server, max 1000 combos
  backtrack(index):
    if now-startTime > timeout return
    if count >=1000 return
    if index>=sortedCourses length:
      if currentCredits >= minRequired (12 normal) && <= creditLimit and no time/exam conflicts unless final_semester:
        score = calculateScore(currentCombo, prefs)
        bestCombos push {combo, credits, score}
      return
    courseGroup = sortedCourses[index]
    backtrack(index+1) // skip
    for spec in courseGroup specs sorted pref prof first:
      if wouldExceedCreditLimit continue
      if hasTimeConflict(spec, currentCombo) and status != final_semester continue
      if hasExamConflict and status != final_semester continue
      currentCombo push spec, currentCredits+=spec.course.credits
      backtrack(index+1)
      pop
  backtrack(0)
  sort by score DESC, return top 15
```
Scoring: freeDays*20 + (-gapHours*10) + preferredProfCount*15 + (-daysWithClasses*5) + morningBonus*5 + creditUtil*10

## UI
Button "پیشنهاد برنامه طلایی" -> modal preferences free days checkboxes, max gap slider 0-6, prefer professors multi-select, prefer morning toggle, Generate button GET /api/v1/golden-schedule with prefs, loading, list 15 cards header "پیشنهاد 1 - امتیاز 85 - 18 واحد - 2 روز خالی" body specs list day/time/professor footer Apply bulk adds to temp

## API
GET /api/v1/golden-schedule?preferFreeDays=thu,fri&maxGap=2&preferProfessors=P1,P2 server 5s limit, cache table GoldenScheduleCache id student_id semester_id preferences_hash combos JSON generated_at expires_at 1h
Client offline compute 3s Web Worker cached data via Workbox

## Caching
Server MySQL GoldenScheduleCache, client IndexedDB GoldenScheduleLocal

## Validation Edge
No valid combo -> "هیچ ترکیب بدون تداخلی با سقف واحد شما یافت نشد", remaining empty -> "تمام دروس پاس شده", creditLimit low limited combos still try, honor final_semester skips time/exam checks more combos

## Performance
Web Worker client not block UI
