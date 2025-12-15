<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $team->name }}</title>
  <style>
    :root {
      --team-primary: {{ $team->primary_color ?? '#2563eb' }};
      --team-secondary: {{ $team->secondary_color ?? '#60a5fa' }};
    }
    body { 
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; 
      margin: 0; 
      padding: 3rem 5vw; 
      font-size: 28px; 
      min-height: 100vh; 
      box-sizing: border-box;
      background: linear-gradient(135deg, color-mix(in srgb, var(--team-primary) 8%, white), color-mix(in srgb, var(--team-secondary) 8%, white));
    }
    .header{ 
      display:flex; 
      gap:2rem; 
      align-items:center; 
      margin-bottom: 3rem;
      padding: 2rem;
      background: color-mix(in srgb, var(--team-primary) 5%, white);
      border-radius: 16px;
      border-left: 6px solid var(--team-primary);
    }
    .logo{ height:200px; object-fit:contain }
    h1{ 
      font-size: 5rem; 
      margin: 0; 
      line-height: 1.2;
      color: color-mix(in srgb, var(--team-primary) 90%, black);
    }
    h2{ 
      font-size: 3.5rem; 
      margin: 2rem 0 1.5rem 0;
      color: color-mix(in srgb, var(--team-primary) 85%, black);
    }
    table{ 
      width:100%; 
      border-collapse:collapse; 
      margin-top:2rem;
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    th, td{ 
      padding:1.5rem 2rem; 
      border-bottom:1px solid color-mix(in srgb, var(--team-primary) 15%, white); 
      text-align:left; 
      font-size: 1.8rem; 
    }
    th{ 
      font-size: 2rem; 
      font-weight: 600; 
      background: color-mix(in srgb, var(--team-primary) 12%, white);
      color: color-mix(in srgb, var(--team-primary) 90%, black);
    }
    tbody tr {
      cursor: pointer;
      transition: background 0.2s;
    }
    tbody tr:hover {
      background: color-mix(in srgb, var(--team-secondary) 8%, white);
    }
    .meta { 
      color: color-mix(in srgb, var(--team-primary) 60%, #6b7280); 
      font-size: 1.8rem; 
      margin-top: 0.5rem; 
    }
    a{ 
      color: var(--team-primary); 
      text-decoration:none; 
      font-size: 1.8rem;
      transition: opacity 0.2s;
    }
    a:hover { opacity: 0.8; }
  </style>
</head>
<body>
  <p><a href="{{ route('teams.index') }}">← All teams</a></p>
  <div class="header">
    @if($team->logo_url)
      <img class="logo" src="{{ $team->logo_url }}" alt="{{ $team->name }} logo" />
    @endif
    <div>
      <h1>{{ $team->name }}</h1>
      <div class="meta">
        {{ $team->city }} · {{ data_get($team->extra_data,'leagues.standard.conference') }} · {{ data_get($team->extra_data,'leagues.standard.division') }}
      </div>
    </div>
  </div>

  <h2>Roster ({{ $team->players->count() }})</h2>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Pos</th>
        <th>Height</th>
        <th>Weight</th>
        <th>Nationality</th>
      </tr>
    </thead>
    <tbody>
      @foreach($team->players as $p)
        <tr onclick="window.location='{{ route('players.show', $p) }}'">
          <td>{{ $p->number }}</td>
          <td>{{ $p->full_name }}</td>
          <td>{{ $p->position }}</td>
          <td>{{ $p->height ? $p->height.' cm' : '' }}</td>
          <td>{{ $p->weight ? $p->weight.' kg' : '' }}</td>
          <td>{{ $p->nationality }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
