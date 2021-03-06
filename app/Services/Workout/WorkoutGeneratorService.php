<?php
namespace App\Services\Workout;

use App\WorkoutCycle;
use App\WorkoutTemplate;
use App\Workout;
use App\WorkoutWeek;

use Carbon\Carbon;

use Illuminate\Support\Collection;

class WorkoutGeneratorService
{

  /**
   * Generates workouts based on created WorkoutCycle
   * WorkoutCycle is created based on WorkoutCycleTemplate,the template have associated WorkoutWeeks that have associated WorkoutDays.
   * Each WorkoutDay is associated with WorkoutTemplates,the template contains information at what time training start/end.
   *
   * @param  WorkoutCycle $workoutCycle [description]
   * @return Collect                     collection of workouts models
   */
    public function generateWorkoutsFromWorkoutCycle(WorkoutCycle $workoutCycle)
    {
        $workouts = collect();

        $cycleStarts = Carbon::parse($workoutCycle->valid_from);
        $cycleEnds = Carbon::parse($workoutCycle->valid_to);
        $currentDate = Carbon::parse($cycleStarts);

        $workoutCycleTemplate = $workoutCycle->workoutCycleTemplate;

        $workoutWeeks = $workoutCycleTemplate->workoutWeeks->sortBy('index');

        while ($currentDate->between($cycleStarts, $cycleEnds)) {
            $workoutWeeks->each(function ($week) use ($currentDate, $workouts, $workoutCycle) {
                $workouts = $workouts->concat($this->generateWorkoutsFromWorkoutDays($week->workoutDays, $currentDate, $workoutCycle));

                $currentDate = $currentDate->addWeek();
            });
        }

        return $workouts;
    }

    public function generateWorkoutsFromWorkoutDays(Collection $workoutDays, Carbon $currentDate, WorkoutCycle $workoutCycle)
    {
        $workouts = collect();

        $workoutDays->each(function ($workoutDay) use ($currentDate, $workouts, $workoutCycle) {
            $workoutTemplates = $workoutDay->workoutTemplates;

            $workoutTemplates->each(function ($workoutTemplate) use ($currentDate, $workoutDay, $workouts, $workoutCycle) {
                $dateTime = $currentDate->copy()->modify('this ' . $workoutDay->day_of_week);
                $workoutStart = Carbon::parse($dateTime->toDateString() .  $workoutTemplate->starts_at);
                $workoutEnd = Carbon::parse($dateTime->toDateString() .  $workoutTemplate->ends_at);

                $cycleStarts = Carbon::parse($workoutCycle->valid_from);
                $cycleEnds = Carbon::parse($workoutCycle->valid_to);

                if ($workoutStart->between($cycleStarts, $cycleEnds) &&  $workoutEnd->between($cycleStarts, $cycleEnds)) {
                    $workout = $this->generateWorkoutFromTemplate($workoutTemplate, $workoutStart, $workoutEnd);
                    $workout->workoutCycle()->associate($workoutCycle->id);
                    $workout->save();

                    $workouts->push($workout);
                }
            });
        });

        return $workouts;
    }

    public function generateWorkoutFromTemplate(WorkoutTemplate $workoutTemplate, string $start, string $end)
    {
        $workout = new Workout();

        $workout->workoutTemplate()->associate($workoutTemplate);
        $workout->start = $start;
        $workout->end = $end;
        $workout->save();

        return $workout;
    }


    public function generateWorkouts($data)
    {
        $workouts = collect([]);
        $workoutDays = collect(data_get($data, 'dayOfWeeks'));
        $startingDate = Carbon::parse(data_get($data, 'dateSpan.from'))->startOfDay();

        $endingDate = Carbon::parse(data_get($data, 'dateSpan.to'))->endOfDay();


        $currentDate = Carbon::parse($startingDate);

        while ($currentDate->lte($endingDate)) {

            $currentWorkouts = $workoutDays->reduce(function ($total, $day) use ($data, $currentDate, $endingDate ) {
                $nextDay = $currentDate->copy()->startOfWeek()->subSeconds(1)->next($day);
               
                if ($nextDay->lt($endingDate)) {
                    $workoutStartDate = $nextDay->copy()->setTimeFromTimeString(data_get($data, 'timeSpan.from'));
                    $workoutEndDate = $workoutStartDate->copy()->setTimeFromTimeString(data_get($data, 'timeSpan.to'));
                 
                    $workout = Workout::create([
                    'name' => data_get($data, 'name'),
                    'start' => $workoutStartDate->copy()->toDateTimeString(),
                    'end' => $workoutEndDate->copy()->toDateTimeString(),
                    ]);

                    return $total->push($workout);
                }

                return $total;
            }, collect());
            
            if ($currentWorkouts->isEmpty()) {
                break;
            }

            $currentDate->addWeek();
            $workouts = $workouts->concat($currentWorkouts);
        }

        return $workouts->keyBy('id');
    }
}
