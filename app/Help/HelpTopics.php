<?php

namespace App\Help;

/**
 * All help topics in display order. Add a topic here and its body as
 * resources/views/filament/help/{id}.blade.php. Link to one with /faq#{id}.
 */
final class HelpTopics
{
    /**
     * @return list<HelpTopic>
     */
    public static function all(): array
    {
        return [
            // Grundlagen: everyone who can open the help
            new HelpTopic(
                id: 'day-view',
                group: 'Grundlagen',
                icon: 'tabler-calendar',
                heading: 'Die Tagesansicht',
                description: 'Bedienung, Vollbild, Durchgestrichenes und PDF-Download.',
                keywords: ['tag', 'plan', 'vollbild', 'pdf', 'leer', 'wochenende', 'aktualisieren'],
            ),
            new HelpTopic(
                id: 'profile',
                group: 'Grundlagen',
                icon: 'tabler-user-circle',
                heading: 'Profil, Anmeldung und Passwort',
                description: 'Anzeigename, Anmeldung über die Schule und was tun ohne Passwort.',
                keywords: ['login', 'passwort vergessen', 'microsoft', 'azure', 'teams', 'email', 'dunkel', 'dark mode'],
            ),

            // Planung
            new HelpTopic(
                id: 'shift-plan',
                group: 'Planung',
                icon: 'tabler-calendar-user',
                heading: 'Schichtplan',
                description: 'Wer ist wann da, wie lange wird gearbeitet und was bedeuten Meins und Alle?',
                permissions: ['page_ShiftPlan'],
                keywords: ['arbeitszeit', 'stunden', 'woche', 'anwesenheit', 'dienstplan'],
            ),
            new HelpTopic(
                id: 'day-view-edit',
                group: 'Planung',
                icon: 'tabler-pencil',
                heading: 'Angebote in der Tagesansicht bearbeiten',
                description: 'Direkt anklicken, „Nur heute“ oder „Alle Wochen“, ausfallen lassen und in leeren Zellen neu anlegen.',
                permissions: ['update_lesson', 'create_lesson', 'create_lesson::template'],
                keywords: ['fällt aus', 'ausfallen', 'vertretung', 'nur heute', 'alle wochen', 'inline'],
            ),
            new HelpTopic(
                id: 'lesson-template',
                group: 'Planung',
                icon: 'tabler-calendar-repeat',
                heading: 'Einzelne und wiederholende Angebote',
                description: 'Warum sich ein Angebot nicht für alle Wochen ändert und was mit vergangenen Tagen passiert.',
                permissions: ['view_lesson', 'view_lesson::template'],
                keywords: ['vorlage', 'wiederholend', 'jede woche', 'rückwirkend', 'vergangenheit', 'archiv'],
            ),
            new HelpTopic(
                id: 'lesson-not-shown',
                group: 'Planung',
                icon: 'tabler-eye-off',
                heading: 'Angebot wird nicht angezeigt',
                description: 'Die häufigsten Gründe, warum ein Angebot im Plan fehlt.',
                permissions: ['view_lesson', 'view_lesson::template'],
                keywords: ['fehlt', 'weg', 'verschwunden', 'ferien', 'feiertag', 'layout'],
            ),
            new HelpTopic(
                id: 'lesson-users-disappear',
                group: 'Planung',
                icon: 'tabler-user-x',
                heading: 'Zugewiesene Benutzer verschwinden aus Angeboten',
                description: 'Benutzer werden automatisch aus der Zuweisung entfernt.',
                permissions: ['view_any_lesson', 'view_any_lesson::template'],
                keywords: ['alle benutzer erlauben', 'personen', 'auswahl'],
            ),
            new HelpTopic(
                id: 'absences',
                group: 'Planung',
                icon: 'tabler-user-minus',
                heading: 'Abwesenheiten eintragen',
                description: 'Krankmeldungen und wo sie im Plan auftauchen.',
                permissions: ['view_absence', 'view_any_absence'],
                keywords: ['krank', 'krankmeldung', 'urlaub', 'abwesend', 'fehlt'],
            ),

            // Bedienung
            new HelpTopic(
                id: 'lesson-columns',
                group: 'Bedienung',
                icon: 'tabler-table',
                heading: 'Spalten in Tabellen anpassen',
                description: 'Nicht alle Details in der Angebotsliste sichtbar.',
                permissions: ['view_lesson'],
                keywords: ['tabelle', 'einblenden'],
            ),

            // Verwaltung
            new HelpTopic(
                id: 'layouts',
                group: 'Verwaltung',
                icon: 'tabler-layout-grid',
                heading: 'Layouts und Abweichungen',
                description: 'Welches Layout an einem Tag gilt, Zellen, Platzhalter und Ferien.',
                permissions: ['view_layout', 'view_layout::deviation'],
                keywords: ['raster', 'ferien', 'feiertag', 'platzhalter', 'mittagessen', 'textgröße', 'abweichung'],
            ),
            new HelpTopic(
                id: 'times',
                group: 'Verwaltung',
                icon: 'tabler-clock',
                heading: 'Zeiten: Beginn und Ende',
                description: 'Woher der Schichtplan die Uhrzeiten nimmt.',
                permissions: ['view_time'],
                keywords: ['uhrzeit', 'dauer', 'stunden'],
            ),
            new HelpTopic(
                id: 'pdfs',
                group: 'Verwaltung',
                icon: 'tabler-file-type-pdf',
                heading: 'PDF-Archiv',
                description: 'Wann PDFs entstehen, was „veraltet“ heißt und wann man nicht neu generieren sollte.',
                permissions: ['view_day::pdf', 'view_any_day::pdf'],
                keywords: ['export', 'archiv', 'veraltet', 'zip', 'drucken'],
            ),
            new HelpTopic(
                id: 'new-users',
                group: 'Verwaltung',
                icon: 'tabler-user-check',
                heading: 'Neue Benutzer freischalten',
                description: 'Neue Konten haben keine Rolle und tauchen deshalb nirgends auf.',
                permissions: ['view_user'],
                keywords: ['rolle', 'ohne rolle', 'gast', 'ext', 'eltern', 'freischalten'],
            ),
            new HelpTopic(
                id: 'import-users',
                group: 'Verwaltung',
                icon: 'tabler-users-group',
                heading: 'Benutzer importieren',
                description: 'Mehrere Benutzer schnell und einfach per CSV-Datei importieren.',
                permissions: ['create_user'],
                keywords: ['csv', 'import', 'excel'],
            ),
            new HelpTopic(
                id: 'roles',
                group: 'Verwaltung',
                icon: 'tabler-shield-lock',
                heading: 'Rollen und Berechtigungen',
                description: 'Was „Ansehen“ und „Alle ansehen“ bedeuten und wer was sieht.',
                permissions: ['view_role'],
                keywords: ['berechtigung', 'rechte', 'admin', 'view_all_shifts'],
            ),
            new HelpTopic(
                id: 'activity-log',
                group: 'Verwaltung',
                icon: 'tabler-history',
                heading: 'Aktivitätsprotokoll',
                description: 'Was protokolliert wird und was nicht.',
                permissions: ['view_activity::log'],
                keywords: ['log', 'wer hat', 'geändert', 'verdächtig'],
            ),
            new HelpTopic(
                id: 'lunch-not-current',
                group: 'Verwaltung',
                icon: 'tabler-alert-triangle',
                heading: 'Mittagessen nicht aktuell',
                description: 'Das Mittagessen für einen bestimmten Tag ist nicht korrekt.',
                permissions: ['update_layout'],
                keywords: ['essen', 'speiseplan', 'cache'],
            ),
        ];
    }
}
