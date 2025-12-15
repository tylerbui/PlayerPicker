<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ $player->full_name }}</title>
  <style>
    :root {
      --team-primary: {{ $player->team->primary_color ?? '#2563eb' }};
      --team-secondary: {{ $player->team->secondary_color ?? '#60a5fa' }};
    }
    * { box-sizing: border-box; }
    body { 
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; 
      margin: 0; 
      padding: 0;
      background: linear-gradient(135deg, color-mix(in srgb, var(--team-primary) 5%, white), color-mix(in srgb, var(--team-secondary) 5%, white));
      font-size: 18px;
    }
    .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
    .back-link { 
      color: var(--team-primary); 
      text-decoration: none; 
      font-size: 1rem;
      display: inline-block;
      margin-bottom: 1rem;
      transition: opacity 0.2s;
    }
    .back-link:hover { opacity: 0.7; }
    
    /* Hero Section */
    .hero {
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 3rem;
      background: white;
      border-radius: 16px;
      padding: 3rem;
      margin-bottom: 2rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      border-left: 6px solid var(--team-primary);
    }
    .player-image {
      width: 300px;
      height: 300px;
      object-fit: cover;
      border-radius: 12px;
      background: color-mix(in srgb, var(--team-primary) 10%, white);
    }
    .player-info h1 {
      font-size: 3.5rem;
      margin: 0 0 0.5rem 0;
      color: color-mix(in srgb, var(--team-primary) 90%, black);
    }
    .player-meta {
      color: #6b7280;
      font-size: 1.3rem;
      margin-bottom: 1.5rem;
    }
    .player-meta a {
      color: var(--team-primary);
      text-decoration: none;
    }
    .player-meta a:hover { text-decoration: underline; }
    .quick-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 1rem;
      margin-top: 2rem;
    }
    .quick-stat {
      background: color-mix(in srgb, var(--team-primary) 8%, white);
      padding: 1rem;
      border-radius: 8px;
      text-align: center;
    }
    .quick-stat-label {
      font-size: 0.9rem;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .quick-stat-value {
      font-size: 1.8rem;
      font-weight: 600;
      color: var(--team-primary);
      margin-top: 0.25rem;
    }

    /* Biography Section */
    .section {
      background: white;
      border-radius: 16px;
      padding: 2.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .section h2 {
      font-size: 2.5rem;
      margin: 0 0 1.5rem 0;
      color: color-mix(in srgb, var(--team-primary) 85%, black);
      border-bottom: 3px solid color-mix(in srgb, var(--team-primary) 20%, white);
      padding-bottom: 0.5rem;
    }
    .biography-text {
      font-size: 1.2rem;
      line-height: 1.8;
      color: #374151;
    }

    /* Stats Tables */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
    }
    .stat-card {
      background: color-mix(in srgb, var(--team-primary) 3%, white);
      padding: 1.5rem;
      border-radius: 12px;
      border: 1px solid color-mix(in srgb, var(--team-primary) 15%, white);
    }
    .stat-card h3 {
      font-size: 1.5rem;
      margin: 0 0 1rem 0;
      color: var(--team-primary);
    }
    .stat-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid color-mix(in srgb, var(--team-primary) 10%, white);
    }
    .stat-row:last-child { border-bottom: none; }
    .stat-label {
      color: #6b7280;
      font-size: 1.1rem;
    }
    .stat-value {
      font-weight: 600;
      color: #111827;
      font-size: 1.1rem;
    }

    /* Recent Games */
    .games-list {
      display: grid;
      gap: 1rem;
    }
    .game-card {
      background: color-mix(in srgb, var(--team-secondary) 5%, white);
      padding: 1.5rem;
      border-radius: 12px;
      display: grid;
      grid-template-columns: 120px 1fr auto;
      gap: 1.5rem;
      align-items: center;
      border-left: 4px solid var(--team-secondary);
    }
    .game-date {
      font-size: 0.9rem;
      color: #6b7280;
      font-weight: 500;
    }
    .game-teams {
      font-size: 1.2rem;
      font-weight: 500;
      color: #111827;
    }
    .game-stats {
      text-align: right;
      color: var(--team-primary);
      font-weight: 600;
      font-size: 1.1rem;
    }

    /* News */
    .news-grid {
      display: grid;
      gap: 1.5rem;
    }
    .news-item {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 1.5rem;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .news-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .news-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #111827;
      margin: 0 0 0.5rem 0;
    }
    .news-meta {
      font-size: 0.9rem;
      color: #6b7280;
      margin-bottom: 0.75rem;
    }
    .news-excerpt {
      font-size: 1.05rem;
      color: #4b5563;
      line-height: 1.6;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      color: #9ca3af;
      font-size: 1.2rem;
    }

    .sync-notice {
      background: #fef3c7;
      border: 1px solid #fbbf24;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      margin-bottom: 2rem;
      font-size: 1rem;
      color: #92400e;
    }
</style>
</head>
<body>
  <div class="container">
    <div id="live-container" style="display:none; margin-bottom: 1rem;">
      <div style="background:#ecfccb;border:1px solid #84cc16;padding:1rem 1.25rem;border-radius:8px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
          <strong id="live-state" style="color:#365314;">Live</strong>
          <span id="live-clock" style="color:#3f6212;"></span>
          <div id="live-line" style="margin-left:auto;color:#1a2e05;font-weight:600;"></div>
        </div>
      </div>
    </div>
    <a class="back-link" href="{{ route('players.index') }}">← All players</a>

    @if($needsSync ?? false)
      <div class="sync-notice">
        ⚠️ Player stats may be outdated. Run: <code>php artisan player:sync-profile {{ $player->id }}</code>
      </div>
    @endif

    <!-- Hero Section -->
    <div class="hero">
      <img class="player-image" src="{{ $player->photo_url }}" alt="{{ $player->full_name }}" />
      <div class="player-info">
        <h1>{{ $player->full_name }}</h1>
        <div class="player-meta">
          <a href="{{ route('teams.show', $player->team) }}">{{ $player->team?->name }}</a> · 
          {{ $player->team?->sport?->name }} · 
          #{{ $player->number }}
        </div>
        
        <div class="quick-stats">
          <div class="quick-stat">
            <div class="quick-stat-label">Position</div>
            <div class="quick-stat-value">{{ $player->position ?? 'N/A' }}</div>
          </div>
          <div class="quick-stat">
            <div class="quick-stat-label">Age</div>
            <div class="quick-stat-value">{{ $player->age ?? 'N/A' }}</div>
          </div>
          <div class="quick-stat">
            <div class="quick-stat-label">Height</div>
            <div class="quick-stat-value">{{ $player->height ? $player->height.' cm' : 'N/A' }}</div>
          </div>
          <div class="quick-stat">
            <div class="quick-stat-label">Weight</div>
            <div class="quick-stat-value">{{ $player->weight ? $player->weight.' kg' : 'N/A' }}</div>
          </div>
          <div class="quick-stat">
            <div class="quick-stat-label">Nationality</div>
            <div class="quick-stat-value">{{ $player->nationality ?? 'N/A' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Biography -->
    @if($player->biography)
      <div class="section">
        <h2>Biography</h2>
        <div class="biography-text">{{ $player->biography }}</div>
      </div>
    @endif

    <!-- Season Stats -->
    @if($player->current_season_stats || $player->previous_season_stats)
      <div class="section">
        <h2>Season Statistics</h2>
        <div class="stats-grid">
          @if($player->current_season_stats)
            <div class="stat-card">
              <h3>2024 Season</h3>
              @foreach($player->current_season_stats as $statGroup)
                @if(isset($statGroup['games']))
                  <div class="stat-row">
                    <span class="stat-label">Games Played</span>
                    <span class="stat-value">{{ $statGroup['games']['appearences'] ?? 0 }}</span>
                  </div>
                @endif
                @if(isset($statGroup['goals']))
                  <div class="stat-row">
                    <span class="stat-label">Goals</span>
                    <span class="stat-value">{{ $statGroup['goals']['total'] ?? 0 }}</span>
                  </div>
                @endif
                @if(isset($statGroup['passes']))
                  <div class="stat-row">
                    <span class="stat-label">Pass Accuracy</span>
                    <span class="stat-value">{{ $statGroup['passes']['accuracy'] ?? 0 }}%</span>
                  </div>
                @endif
              @endforeach
            </div>
          @endif

          @if($player->previous_season_stats)
            <div class="stat-card">
              <h3>2023 Season</h3>
              @foreach($player->previous_season_stats as $statGroup)
                @if(isset($statGroup['games']))
                  <div class="stat-row">
                    <span class="stat-label">Games Played</span>
                    <span class="stat-value">{{ $statGroup['games']['appearences'] ?? 0 }}</span>
                  </div>
                @endif
                @if(isset($statGroup['goals']))
                  <div class="stat-row">
                    <span class="stat-label">Goals</span>
                    <span class="stat-value">{{ $statGroup['goals']['total'] ?? 0 }}</span>
                  </div>
                @endif
                @if(isset($statGroup['passes']))
                  <div class="stat-row">
                    <span class="stat-label">Pass Accuracy</span>
                    <span class="stat-value">{{ $statGroup['passes']['accuracy'] ?? 0 }}%</span>
                  </div>
                @endif
              @endforeach
            </div>
          @endif
        </div>
      </div>
    @endif

    <!-- Recent Games -->
    @if($player->recent_games_stats && count($player->recent_games_stats) > 0)
      <div class="section">
        <h2>Recent Games</h2>
        <div class="games-list">
          @foreach(array_slice($player->recent_games_stats, 0, 10) as $game)
            <div class="game-card">
              <div class="game-date">{{ $game['fixture']['date'] ?? 'N/A' }}</div>
              <div class="game-teams">
                {{ $game['teams']['home']['name'] ?? 'Home' }} vs {{ $game['teams']['away']['name'] ?? 'Away' }}
              </div>
              <div class="game-stats">
                {{ $game['goals']['home'] ?? 0 }} - {{ $game['goals']['away'] ?? 0 }}
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <!-- News -->
    @if($player->news && count($player->news) > 0)
      <div class="section">
        <h2>Latest News</h2>
        <div class="news-grid">
          @foreach($player->news as $article)
            <div class="news-item">
              <h3 class="news-title"><a href="{{ $article['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">{{ $article['title'] ?? 'No Title' }}</a></h3>
              <div class="news-meta">
                {{ $article['source'] ?? 'Unknown Source' }} · {{ $article['date'] ?? 'N/A' }}
              </div>
              <p class="news-excerpt">{{ $article['excerpt'] ?? '' }}</p>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    @if(!$player->biography && !$player->current_season_stats && !$player->recent_games_stats && !$player->news)
      <div class="section">
        <div class="empty-state">
          No detailed profile data available yet.<br>
          Run <code>php artisan player:sync-profile {{ $player->id }}</code> to fetch player data.
        </div>
      </div>
    @endif
    </div>
    <script>
      (function(){
        const container = document.getElementById('live-container');
        const stateEl = document.getElementById('live-state');
        const clockEl = document.getElementById('live-clock');
        const lineEl = document.getElementById('live-line');
        const slug = @json($player->slug);
        const url = `/api/v1/players/${slug}/live`;

        async function tick() {
          try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
              // Hide banner if no game
              container.style.display = 'none';
              return;
            }
            const data = await res.json();
            const live = !!data.live;
            const state = data.state || 'pre';
            const clock = data.clock || '';
            const L = data.line || null;

            stateEl.textContent = live ? 'Live' : (state === 'pre' ? 'Pregame' : 'Final');
            clockEl.textContent = clock;

            if (L) {
              const parts = [];
              if (L.minutes) parts.push(`${L.minutes}m`);
              if (L.pts != null) parts.push(`${L.pts} pts`);
              if (L.reb != null) parts.push(`${L.reb} reb`);
              if (L.ast != null) parts.push(`${L.ast} ast`);
              if (L.stl != null) parts.push(`${L.stl} stl`);
              if (L.blk != null) parts.push(`${L.blk} blk`);
              if (L.tov != null) parts.push(`${L.tov} TO`);
              lineEl.textContent = parts.join(' · ');
            } else {
              lineEl.textContent = 'Line not available yet';
            }

            container.style.display = 'block';
          } catch (e) {
            // network error: keep silent
          }
        }

        // initial + poll
        tick();
        setInterval(tick, 15000);
      })();
    </script>
  </div>
</body>
</html>
