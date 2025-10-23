<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $date->translatedFormat('l, d.m.Y') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Ubuntu', sans-serif;
            font-size: {{ $textSize }}%;
            color: black;
            background: white;
        }

        table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            padding: 8px;
            border: 2px solid white;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        td small {
            display: block;
            font-size: 0.85em;
            margin-bottom: 2px;
        }

        td strong {
            display: block;
            font-weight: bold;
            margin: 2px 0;
        }

        s {
            text-decoration: line-through;
        }
    </style>
</head>
<body>
    <table>
        <tbody>
        @foreach ($layout as $row)
            <tr>
                @foreach ($row as $cell)
                    @if (!isset($cell['hidden']))
                        @php
                            $lesson = collect($lessons)->first(function ($lesson) use ($cell) {
                                return $lesson['room'] == $cell['room'] && $lesson['lesson_time'] == $cell['time'];
                            });
                        @endphp
                        <td
                            @if (isset($cell['colspan']) && $cell['colspan'] > 1)
                                colspan="{{ $cell['colspan'] }}"
                            @endif
                            @if (isset($cell['rowspan']) && $cell['rowspan'] > 1)
                                rowspan="{{ $cell['rowspan'] }}"
                            @endif
                            style="
                                color: black;
                                border: 2px solid white;
                                text-align: {{ $cell['alignment'] ?? 'left' }};
                                @if (!empty($lesson['color']) && isset($colors[$lesson['color']]))
                                    background-color: {{ $colors[$lesson['color']] }};
                                @elseif(isset($cell['color']) && isset($colors[$cell['color']]))
                                    background-color: {{ $colors[$cell['color']] }};
                                @endif
                            "
                        >
                            @if(isset($lesson))
                                @if($lesson['disabled'])
                                    <small>
                                        <s>
                                            @foreach($lesson['assigned_users'] as $userName)
                                                {{ $userName }}@if(!$loop->last), @endif
                                            @endforeach
                                        </s>
                                    </small>

                                    <strong><s>{!! $lesson['name'] ?? '' !!}</s></strong>
                                    <s>{!! $lesson['description'] ?? '' !!}</s>
                                @else
                                    <small>
                                        @foreach($lesson['assigned_users'] as $userName)
                                            {{ $userName }}@if(!$loop->last), @endif
                                        @endforeach
                                    </small>

                                    <strong>{!! $lesson['name'] ?? '' !!}</strong>
                                    {!! $lesson['description'] ?? '' !!}
                                @endif
                            @else
                                @php
                                    // Simple placeholder replacement for PDF
                                    $displayName = $cell['displayName'] ?? '';
                                    $dayName = $date->translatedFormat('D');
                                    $dayFull = $date->translatedFormat('d.m.Y');

                                    $absencesStr = "";
                                    foreach ($absences as $key => $absence) {
                                        $absencesStr .= $absence['display_name'] . ($key === array_key_last($absences) ? '' : ', ');
                                    }

                                    $displayName = str_replace('%tag%', str_replace('.', '', $dayName) . " " . $dayFull, $displayName);
                                    $displayName = str_replace('%abwesenheit%', $absencesStr, $displayName);
                                @endphp
                                {!! $displayName !!}
                            @endif
                        </td>

                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
