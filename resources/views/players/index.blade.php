<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Players</title>
  <style>
    * { box-sizing: border-box; }
    body { 
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; 
      margin: 0; 
      padding: 3rem 5vw;
      font-size: 24px;
      min-height: 100vh;
      background: #f9fafb;
    }
    h1 { 
      font-size: 5rem; 
      margin: 0 0 2rem 0; 
      color: #111827;
    }
    .grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); 
      gap: 2rem; 
      margin-bottom: 2rem;
    }
    .card { 
      border: 2px solid #e5e7eb; 
      border-radius: 16px; 
      padding: 2.5rem; 
      background: #fff;
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 8px;
      background: var(--card-color, #2563eb);
    }
    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.15);
      background: linear-gradient(135deg, color-mix(in srgb, var(--card-color, #2563eb) 3%, white), white);
    }
    .card h3 {
      font-size: 2.5rem;
      margin: 0 0 1rem 0;
      color: #111827;
    }
    .meta { 
      color: #6b7280; 
      font-size: 1.4rem;
      line-height: 1.6;
      margin-bottom: 0.5rem;
    }
    .search { 
      margin-bottom: 3rem; 
      display: flex; 
      gap: 1.5rem; 
      flex-wrap: wrap;
      align-items: center;
    }
    input, select { 
      padding: 1.2rem 1.5rem; 
      border: 2px solid #e5e7eb; 
      border-radius: 12px;
      font-size: 1.4rem;
      min-width: 250px;
      flex: 1;
    }
    input:focus, select:focus {
      outline: none;
      border-color: #2563eb;
    }
    button { 
      padding: 1.2rem 2rem; 
      border: none; 
      border-radius: 12px; 
      background: #111827; 
      color: #fff;
      font-size: 1.4rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    button:hover {
      background: #374151;
    }
    a { 
      color: #111827; 
      text-decoration: none;
    }
  </style>
</head>
<body>
  <h1>Players</h1>

  <form class="search" method="get" action="{{ route('players.index') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name…" />
    <select name="team_id">
      <option value="">All teams</option>
      @foreach($teams as $t)
        <option value="{{ $t->id }}" @selected((string)request('team_id')===(string)$t->id)>{{ $t->name }}</option>
      @endforeach
    </select>
    <input type="text" name="position" value="{{ request('position') }}" placeholder="Position (e.g. G, F, C)" />
    <button type="submit">Filter</button>
  </form>

  <div class="grid">
    @foreach($players as $p)
      <a class="card" href="{{ route('players.show', $p) }}" style="--card-color: {{ $p->team?->primary_color ?? '#2563eb' }}">
        <h3>{{ $p->full_name }}</h3>
        <div class="meta">
          {{ $p->team?->name }} · {{ $p->position }} @if($p->number) · #{{ $p->number }} @endif
        </div>
        <div class="meta">
          {{ $p->nationality }} @if($p->height) · {{ $p->height }} cm @endif @if($p->weight) · {{ $p->weight }} kg @endif
        </div>
      </a>
    @endforeach
  </div>

  <div style="margin-top:1rem;">
    {{ $players->links() }}
  </div>
</body>
</html>