<?php


namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @property int $channel_id
 * @property string $name
 * @property string $begin_date_time
 * @property string $end_date_time
 */
class Customer extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'mintos_clients';

    /**
     * Return currently on-air programme for concrete channel_id + we need to change end date from beginning next show
     * !notice!:
     * I don’t look at the end time of the program, as I understand there must always be something on the air
     * and the next added program will change the end time!
     * @param $channelNr [1-3]
     * @return string
     */
    public function isCustomerExists(int $customerId): string
    {
        $customers = $this::query()
            ->where('unique_client_id', $customerId)
            ->get();
        return $customers->count();
    }

    /**
     * Return one day programme for concrete channel_id,
     * searching data between: day beginning (06:00:00) and (next day 05:59:59)
     * @param int $channelNr
     * @param string $date
     * @return string
     */
    public function getGuide(int $channelNr, string $date): string
    {
        $dateFrom = Carbon::parse($date)->addHours(self::NEW_DAY_STARTS);
        $dateTo = Carbon::parse($dateFrom)->addHours(23)->addMinutes(59)->addSeconds(59);
        $allProgrammes = $this::query()->where('channel_id', $channelNr)
            ->whereBetween('begin_date_time', [$dateFrom, $dateTo])
            ->orderBy('begin_date_time', 'ASC')
            ->get();
        return json_encode($this->prepareDataOnFly($allProgrammes)) ?: '';
    }

    /**
     * Show 10 upcoming programmes from currently on air programme
     * If on-air programme are not founded -> from current date
     * @param int $channelNr
     * @param int $limit
     * @return string
     */
    public function upcomingProgrammes(int $channelNr, int $limit = 10): string
    {
        $onAirArray = $this->validateAndConvertJsonToArray($this->getOnAirProgrammes($channelNr));
        if (empty($onAirArray) || isset($onAirArray['error'])) {
            $beginDate = Carbon::now();
        } else {
            $beginDate = $onAirArray[0]['Sākuma datums & laiks'];
        }
        $upcoming = $this::query()->where('channel_id', $channelNr)
            ->where('begin_date_time', '>=', $beginDate)
            ->orderBy('begin_date_time', 'ASC')
            ->limit($limit)
            ->get();
        return json_encode($this->prepareDataOnFly($upcoming)) ?: '';
    }

    /**
     * Function will add new programme to database and check for existing record by date - channelID
     * All fields are already validated in controller.
     * @param Request $request
     * @return JsonResponse
     */
    public function addNewProgramme(Request $request): JsonResponse
    {
        $programWithBeginTimeExists = $this::query()
            ->where('channel_id', $request->input('channel_nr'))
            ->where('begin_date_time', $request->input('begin_date'))
            ->exists();

        if ($programWithBeginTimeExists) {
            return response()->json([
                'error' => [
                    "Programma ar sākumu laiku " . $request->input('begin_date') . " kanālam "
                    . $request->input('channel_nr') . " jau ir ierakstita datu bazē"
                ],
            ], 400);
        }

        $this->channel_id = $request->input('channel_nr');
        $this->name = $request->input('name');
        $this->begin_date_time = $request->input('begin_date');
        $this->end_date_time = $request->input('end_date');;
        $this->save();
        return response()->json($this, 201);
    }

    /**
     * Format date to our [Riga] local format
     * @param $date
     * @return Carbon
     */
    public function formatDateToRigaTimeZone(Carbon $date): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $date->toDateTimeString(), 'UTC')
            ->setTimezone(self::TIME_ZONE);
    }

    /**
     * Prepare data for user
     * if next programme begin_time is greater than previous programme end_time ,
     * then update on-fly (without updating database) prev. programme end_time.
     * @param object $allProgrammes
     * @return array
     */
    public function prepareDataOnFly(object $allProgrammes): array
    {
        if (!$allProgrammes->isEmpty()) {
            $answer = [];
            $prevData = [];
            foreach ($allProgrammes as $program) {
                if (!empty($prevData) && $program->begin_date_time != $prevData['end_date']) {
                    $answer[$prevData['id']]['Beigu datums & laiks'] = $program->begin_date_time;
                }
                $prevData = [
                    'id' => $program->id,
                    'end_date' => $program->end_date_time
                ];
                $answer[$program->id] = [
                    'Kanāls' => $program->channel_id,
                    'Nosaukums' => $program->name,
                    'Sākuma datums & laiks' => $program->begin_date_time,
                    'Beigu datums & laiks' => $program->end_date_time,
                ];
            }
            return array_values($answer);
        } else {
            return ['error' => ['Nav atrasta neviena programma!']];
        }
    }

    /**
     * In future need to replace function in helper
     * We validate json and return it as array
     * @param string $json
     * @return array<array>
     */
    public function validateAndConvertJsonToArray(string $json): array
    {
        $jsonArray = [];
        if (json_validate($json)) {
            $jsonArray = json_decode($json, true);
        }
        return $jsonArray;
    }
}
