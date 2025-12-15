<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Teams</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { overflow-x: hidden; }
    body { 
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; 
      padding: 2rem; 
      min-height: 100vh;
      background: #f9fafb;
      max-width: 100vw;
      overflow-x: hidden;
    }
    .grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(min(400px, 100%), 1fr)); 
      gap: 2.5rem;
      margin-bottom: 2rem;
      width: 100%;
    }
    .card { 
      border: 1px solid #e5e7eb; 
      border-radius: 16px; 
      padding: 3.5rem; 
      background: #fff;
      transition: all 0.2s ease;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      gap: 1.75rem;
      min-height: 380px;
    }
    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.15);
      border-color: #111827;
    }
    .logo { 
      height: 120px; 
      object-fit: contain; 
      margin: 0 auto;
    }
    .card h3 {
      font-size: 1.5rem;
      margin-top: auto;
    }
    .meta { 
      color: #6b7280; 
      font-size: .95rem; 
      line-height: 1.5;
    }
    .search { 
      margin-bottom: 2.5rem; 
      display:flex; 
      gap: 1rem;
      flex-wrap: wrap;
      align-items: stretch;
    }
    input[type="text"], select { 
      padding: 1.25rem 1.5rem; 
      border: 2px solid #e5e7eb; 
      border-radius: 12px; 
      font-size: 1.25rem;
    }
    input[type="text"] { 
      width: 450px;
      flex: 1;
      min-width: 300px;
    }
    select {
      min-width: 220px;
      font-weight: 500;
    }
    button{ 
      padding: 1.25rem 2.5rem; 
      border: 2px solid #111827; 
      border-radius: 12px; 
      background: #111827; 
      color: #fff;
      cursor: pointer;
      font-weight: 600;
      font-size: 1.25rem;
      transition: all 0.2s;
    }
    button:hover {
      background: #374151;
      border-color: #374151;
    }
    a{ color:#111827; text-decoration:none }
    h1 {
      margin-bottom: 2rem;
      font-size: 3rem;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <h1>NBA Teams</h1>

  <form class="search" method="get" action="{{ route('teams.index') }}">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search team, city, or code…" />
    <select name="conference">
      <option value="">All conferences</option>
      @foreach(['East','West'] as $c)
        <option value="{{ $c }}" @selected(request('conference')===$c)>{{ $c }}</option>
      @endforeach
    </select>
    <select name="division">
      <option value="">All divisions</option>
      @foreach(['Atlantic','Central','Southeast','Northwest','Pacific','Southwest'] as $d)
        <option value="{{ $d }}" @selected(request('division')===$d)>{{ $d }}</option>
      @endforeach
    </select>
    <button type="submit">Filter</button>
  </form>

  <div class="grid">
    @foreach($teams as $team)
      <a class="card" href="{{ route('teams.show', $team) }}">
        @if($team->logo_url)
          <img class="logo" src="{{ $team->logo_url }}" alt="{{ $team->name }} logo" />
        @endif
        <h3>{{ $team->name }}</h3>
        <div class="meta">
          {{ $team->city }} · {{ data_get($team->extra_data,'leagues.standard.conference') }} · {{ data_get($team->extra_data,'leagues.standard.division') }}
        </div>
        <div class="meta">Players: {{ $team->players_count }}</div>
      </a>
    @endforeach
  </div>

  <div style="margin-top:1rem;">
    {{ $teams->links() }}
  </div>
</body>
</html>