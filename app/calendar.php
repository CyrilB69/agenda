<?php
declare(strict_types=1);

function week_start_from_query(?string $value): DateTimeImmutable
{
    $timezone = new DateTimeZone(date_default_timezone_get());
    $date = $value
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone)
        : new DateTimeImmutable('today', $timezone);

    if (!$date) {
        $date = new DateTimeImmutable('today', $timezone);
    }

    $date = $date->setTime(0, 0);
    $offset = (int) $date->format('N') - 1;

    return $offset > 0 ? $date->modify("-{$offset} days") : $date;
}

function week_days(DateTimeImmutable $weekStart): array
{
    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $days[] = $weekStart->modify("+{$i} days");
    }

    return $days;
}

function calendar_items_grouped_by_room_and_day(array $rooms, array $reservations, array $blackouts, array $days): array
{
    $grouped = [];

    foreach ($reservations as $reservation) {
        $date = (new DateTimeImmutable($reservation['starts_at']))->format('Y-m-d');
        $reservation['_type'] = 'reservation';
        $grouped[(int) $reservation['room_id']][$date][] = $reservation;
    }

    foreach ($blackouts as $blackout) {
        foreach ($rooms as $room) {
            if ($blackout['room_id'] !== null && (int) $blackout['room_id'] !== (int) $room['id']) {
                continue;
            }

            foreach ($days as $day) {
                $dayStart = $day->setTime(0, 0);
                $dayEnd = $dayStart->modify('+1 day');

                if ($blackout['starts_at'] >= db_datetime($dayEnd) || $blackout['ends_at'] <= db_datetime($dayStart)) {
                    continue;
                }

                $blackout['_type'] = 'blackout';
                $blackout['room_id'] = (int) $room['id'];
                $grouped[(int) $room['id']][$day->format('Y-m-d')][] = $blackout;
            }
        }
    }

    foreach ($grouped as $roomId => $daysItems) {
        foreach ($daysItems as $dayKey => $items) {
            usort($items, static fn (array $a, array $b): int => strcmp($a['starts_at'], $b['starts_at']));
            $grouped[$roomId][$dayKey] = $items;
        }
    }

    return $grouped;
}

function render_calendar(array $rooms, array $reservations, DateTimeImmutable $weekStart, string $page = 'home', array $blackouts = []): void
{
    $days = week_days($weekStart);
    $weekEnd = $weekStart->modify('+6 days');
    $previous = $weekStart->modify('-7 days')->format('Y-m-d');
    $next = $weekStart->modify('+7 days')->format('Y-m-d');
    $grouped = calendar_items_grouped_by_room_and_day($rooms, $reservations, $blackouts, $days);
    ?>
    <section class="calendar-shell" aria-label="Agenda hebdomadaire">
        <div class="calendar-toolbar">
            <div>
                <p class="eyebrow">Semaine affichée</p>
                <h2>Du <?= e($weekStart->format('d/m/Y')) ?> au <?= e($weekEnd->format('d/m/Y')) ?></h2>
            </div>
            <div class="toolbar-actions">
                <a class="button secondary" href="<?= e(url_for($page, ['week' => $previous])) ?>">Précédente</a>
                <a class="button secondary" href="<?= e(url_for($page)) ?>">Aujourd'hui</a>
                <a class="button secondary" href="<?= e(url_for($page, ['week' => $next])) ?>">Suivante</a>
            </div>
        </div>

        <?php if (!$rooms): ?>
            <div class="empty-state">Aucune salle active pour le moment.</div>
        <?php endif; ?>

        <div class="rooms-calendar">
            <?php foreach ($rooms as $room): ?>
                <article class="room-schedule" style="--room-color: <?= e($room['color']) ?>">
                    <header class="room-header">
                        <div>
                            <h3><?= e($room['name']) ?></h3>
                            <?php if (!empty($room['capacity'])): ?>
                                <p><?= e($room['capacity']) ?> personnes</p>
                            <?php endif; ?>
                        </div>
                        <span class="room-dot" aria-hidden="true"></span>
                    </header>

                    <div class="day-grid">
                        <?php foreach ($days as $day): ?>
                            <?php
                            $dayKey = $day->format('Y-m-d');
                            $items = $grouped[(int) $room['id']][$dayKey] ?? [];
                            ?>
                            <section class="day-column">
                                <h4><?= e(human_date($day)) ?></h4>
                                <?php if (!$items): ?>
                                    <p class="day-empty">Libre</p>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <?php $isBlackout = ($item['_type'] ?? 'reservation') === 'blackout'; ?>
                                        <?php if ($isBlackout): ?>
                                            <div class="reservation-chip status-blackout">
                                                <strong><?= e(time_range($item['starts_at'], $item['ends_at'])) ?></strong>
                                                <span>Indisponible - <?= e($item['title']) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <a class="reservation-chip <?= e(status_class($item['status'])) ?>" href="<?= e(url_for('reservation', ['id' => (int) $item['id']])) ?>">
                                                <strong><?= e(time_range($item['starts_at'], $item['ends_at'])) ?></strong>
                                                <span><?= e($item['title']) ?></span>
                                                <?php if ($item['status'] === 'pending'): ?>
                                                    <em>en attente</em>
                                                <?php endif; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}
