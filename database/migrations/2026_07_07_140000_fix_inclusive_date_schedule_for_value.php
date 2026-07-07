<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW attendance_undertime_view AS
            WITH matched_schedules AS (
                SELECT
                    a.employee_id, a.date,
                    a.post1_time, a.post2_time, a.post3_time, a.post4_time,
                    ws.id            AS schedule_id,
                    ws.timein_AM, ws.timeout_AM, ws.timein_PM, ws.timeout_PM,
                    ws.no_lunch_gap,
                    ROW_NUMBER() OVER (
                        PARTITION BY a.employee_id, a.date
                        ORDER BY CASE ws.schedule_for
                            WHEN 'day_in_week'     THEN 1
                            WHEN 'inclusive date'  THEN 2
                            WHEN 'everyday'        THEN 3
                            ELSE 4
                        END
                    ) AS rn
                FROM attendance_daily_view a
                JOIN work_schedules ws
                    ON  ws.employee_id = a.employee_id
                    AND ws.deleted_at IS NULL
                    AND (
                        ws.schedule_for = 'everyday'
                        OR (ws.schedule_for = 'day_in_week'    AND JSON_CONTAINS(ws.days, JSON_QUOTE(DAYNAME(a.date))))
                        OR (ws.schedule_for = 'inclusive date' AND a.date BETWEEN ws.from_date AND ws.to_date)
                    )
            ),
            resolved AS (
                SELECT
                    employee_id, date,
                    CAST(TIME_FORMAT(post1_time, '%H:%i:00') AS TIME) AS post1_time,
                    CAST(TIME_FORMAT(post2_time, '%H:%i:00') AS TIME) AS post2_time,
                    CAST(TIME_FORMAT(post3_time, '%H:%i:00') AS TIME) AS post3_time,
                    CAST(TIME_FORMAT(post4_time, '%H:%i:00') AS TIME) AS post4_time,
                    schedule_id, timein_AM, timeout_AM, timein_PM, timeout_PM, no_lunch_gap
                FROM matched_schedules WHERE rn = 1
            ),
            grace AS (
                SELECT
                    e.id AS employee_id,
                    COALESCE(
                        (SELECT wtr.time FROM work_time_rules wtr WHERE wtr.rule = 'Grace Period' AND wtr.offices  = e.office_id LIMIT 1),
                        (SELECT wtr.time FROM work_time_rules wtr WHERE wtr.rule = 'Grace Period' AND wtr.offices IS NULL        LIMIT 1),
                        15
                    ) AS grace_minutes
                FROM employees e
            ),
            raw AS (
                SELECT
                    r.*,
                    g.grace_minutes,
                    GREATEST(COALESCE(TIMESTAMPDIFF(MINUTE, r.timein_AM,  r.post1_time), 0), 0) AS raw_am,
                    GREATEST(COALESCE(TIMESTAMPDIFF(MINUTE, r.post2_time, r.timeout_AM), 0), 0) AS raw_lunch_out,
                    GREATEST(COALESCE(TIMESTAMPDIFF(MINUTE, r.timein_PM,  r.post3_time), 0), 0) AS raw_pm,
                    GREATEST(COALESCE(TIMESTAMPDIFF(MINUTE, r.post4_time, r.timeout_PM), 0), 0) AS raw_eod
                FROM resolved r
                LEFT JOIN grace g ON g.employee_id = r.employee_id
            )
            SELECT
                r.employee_id,
                r.date,
                r.timein_AM         AS sched_timein_AM,
                r.post1_time        AS actual_timein_AM,
                r.timeout_AM        AS sched_timeout_AM,
                r.post2_time        AS actual_timeout_AM,
                r.timein_PM         AS sched_timein_PM,
                r.post3_time        AS actual_timein_PM,
                r.timeout_PM        AS sched_timeout_PM,
                r.post4_time        AS actual_timeout_PM,
                r.grace_minutes,
                CASE WHEN r.raw_am        <= r.grace_minutes THEN 0 ELSE r.raw_am        END AS late_minutes_AM,
                CASE WHEN r.raw_lunch_out <= r.grace_minutes THEN 0 ELSE r.raw_lunch_out END AS undertime_lunch_out_minutes,
                CASE WHEN r.raw_pm        <= r.grace_minutes THEN 0 ELSE r.raw_pm        END AS late_minutes_PM,
                CASE WHEN r.raw_eod       <= r.grace_minutes THEN 0 ELSE r.raw_eod       END AS undertime_end_of_day_minutes,
                CASE WHEN r.no_lunch_gap = 1 THEN (
                         (CASE WHEN r.raw_am  <= r.grace_minutes THEN 0 ELSE r.raw_am  END)
                       + (CASE WHEN r.raw_eod <= r.grace_minutes THEN 0 ELSE r.raw_eod END)
                     )
                     ELSE (
                         (CASE WHEN r.raw_am        <= r.grace_minutes THEN 0 ELSE r.raw_am        END)
                       + (CASE WHEN r.raw_lunch_out <= r.grace_minutes THEN 0 ELSE r.raw_lunch_out END)
                       + (CASE WHEN r.raw_pm        <= r.grace_minutes THEN 0 ELSE r.raw_pm        END)
                       + (CASE WHEN r.raw_eod       <= r.grace_minutes THEN 0 ELSE r.raw_eod       END)
                     )
                END AS total_undertime_minutes
            FROM raw r
        ");
    }

    public function down(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW attendance_undertime_view AS
            WITH matched_schedules AS (
                SELECT
                    a.employee_id, a.date,
                    a.post1_time, a.post2_time, a.post3_time, a.post4_time,
                    ws.id            AS schedule_id,
                    ws.timein_AM, ws.timeout_AM, ws.timein_PM, ws.timeout_PM,
                    ws.no_lunch_gap,
                    ROW_NUMBER() OVER (
                        PARTITION BY a.employee_id, a.date
                        ORDER BY CASE ws.schedule_for
                            WHEN 'day_in_week'     THEN 1
                            WHEN 'inclusive_date'  THEN 2
                            WHEN 'everyday'        THEN 3
                            ELSE 4
                        END
                    ) AS rn
                FROM attendance_daily_view a
                JOIN work_schedules ws
                    ON  ws.employee_id = a.employee_id
                    AND ws.deleted_at IS NULL
                    AND (
                        ws.schedule_for = 'everyday'
                        OR (ws.schedule_for = 'day_in_week'    AND JSON_CONTAINS(ws.days, JSON_QUOTE(DAYNAME(a.date))))
                        OR (ws.schedule_for = 'inclusive_date' AND a.date BETWEEN ws.from_date AND ws.to_date)
                    )
            ),
            resolved AS (
                SELECT
                    employee_id, date,
                    CAST(TIME_FORMAT(post1_time, '%H:%i:00') AS TIME) AS post1_time,
                    CAST(TIME_FORMAT(post2_time, '%H:%i:00') AS TIME) AS post2_time,
                    CAST(TIME_FORMAT(post3_time, '%H:%i:00') AS TIME) AS post3_time,
                    CAST(TIME_FORMAT(post4_time, '%H:%i:00') AS TIME) AS post4_time,
                    schedule_id, timein_AM, timeout_AM, timein_PM, timeout_PM, no_lunch_gap
                FROM matched_schedules WHERE rn = 1
            ),
            grace AS (
                SELECT
                    e.id AS employee_id,
                    COALESCE(
                        (SELECT wtr.time FROM work_time_rules wtr WHERE wtr.rule = 'Grace Period' AND wtr.offices  = e.office_id LIMIT 1),
                        (SELECT wtr.time FROM work_time_rules wtr WHERE wtr.rule = 'Grace Period' AND wtr.offices IS NULL        LIMIT 1),
                        15
                    ) AS grace_minutes
                FROM employees e
            ),
            raw AS (
                SELECT
                    r.*,
                    g.grace_minutes,
                    GREATEST(COALESCE(TIMESTAMPDIFF(MINUTE, r.timein_AM,  r.post1_time), 0), 0) AS raw_am,
                    GREATEST(COALESCE(TIMESTAMPDIFF(MINUTE, r.post2_time, r.timeout_AM), 0), 0) AS raw_lunch_out,
                    GREATEST(COALESCE(TIMESTAMPDIFF(MINUTE, r.timein_PM,  r.post3_time), 0), 0) AS raw_pm,
                    GREATEST(COALESCE(TIMESTAMPDIFF(MINUTE, r.post4_time, r.timeout_PM), 0), 0) AS raw_eod
                FROM resolved r
                LEFT JOIN grace g ON g.employee_id = r.employee_id
            )
            SELECT
                r.employee_id,
                r.date,
                r.timein_AM         AS sched_timein_AM,
                r.post1_time        AS actual_timein_AM,
                r.timeout_AM        AS sched_timeout_AM,
                r.post2_time        AS actual_timeout_AM,
                r.timein_PM         AS sched_timein_PM,
                r.post3_time        AS actual_timein_PM,
                r.timeout_PM        AS sched_timeout_PM,
                r.post4_time        AS actual_timeout_PM,
                r.grace_minutes,
                CASE WHEN r.raw_am        <= r.grace_minutes THEN 0 ELSE r.raw_am        END AS late_minutes_AM,
                CASE WHEN r.raw_lunch_out <= r.grace_minutes THEN 0 ELSE r.raw_lunch_out END AS undertime_lunch_out_minutes,
                CASE WHEN r.raw_pm        <= r.grace_minutes THEN 0 ELSE r.raw_pm        END AS late_minutes_PM,
                CASE WHEN r.raw_eod       <= r.grace_minutes THEN 0 ELSE r.raw_eod       END AS undertime_end_of_day_minutes,
                CASE WHEN r.no_lunch_gap = 1 THEN (
                         (CASE WHEN r.raw_am  <= r.grace_minutes THEN 0 ELSE r.raw_am  END)
                       + (CASE WHEN r.raw_eod <= r.grace_minutes THEN 0 ELSE r.raw_eod END)
                     )
                     ELSE (
                         (CASE WHEN r.raw_am        <= r.grace_minutes THEN 0 ELSE r.raw_am        END)
                       + (CASE WHEN r.raw_lunch_out <= r.grace_minutes THEN 0 ELSE r.raw_lunch_out END)
                       + (CASE WHEN r.raw_pm        <= r.grace_minutes THEN 0 ELSE r.raw_pm        END)
                       + (CASE WHEN r.raw_eod       <= r.grace_minutes THEN 0 ELSE r.raw_eod       END)
                     )
                END AS total_undertime_minutes
            FROM raw r
        ");
    }
};
