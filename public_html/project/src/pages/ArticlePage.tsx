import { useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import Nav from '../components/Nav';
import Footer from '../components/Footer';
import ArticleMap from '../components/article/ArticleMap';
import ElevationChart from '../components/article/ElevationChart';

const trails = [
  { name: 'Sidewinder',          rating: 'Green', ratingClass: 'green' },
  { name: 'East Branch',         rating: 'Green', ratingClass: 'green' },
  { name: 'Coronary Bypass',     rating: 'Blue',  ratingClass: 'blue'  },
  { name: 'Kitchel',             rating: 'Blue',  ratingClass: 'blue'  },
  { name: 'Burnham',             rating: 'Black', ratingClass: 'black' },
  { name: 'Sidewinder (return)', rating: 'Green', ratingClass: 'green' },
];

const conditions = [
  { label: 'Surface',  value: 'Natural'     },
  { label: 'Best in',  value: 'Summer'      },
  { label: 'Mud Risk', value: 'High Spring' },
  { label: 'Dogs',     value: 'Allowed'     },
];

const ratingStyles: Record<string, React.CSSProperties> = {
  blue:  { background: '#ddeeff', color: '#1a4470', border: '1px solid #aaccee' },
  black: { background: '#222',    color: '#fff'    },
  green: { background: '#e6f2e8', color: '#2d4a33', border: '1px solid #b8d4ba' },
};

const stats = [
  { label: 'Distance',   value: '12.7',          unit: 'miles'           },
  { label: 'Elevation',  value: '2,600',          unit: 'ft gain'         },
  { label: 'Est. Time',  value: '2.5–3',          unit: 'hours'           },
  { label: 'Difficulty', value: 'Intermediate',   unit: 'blue / some black', text: true },
  { label: 'Season',     value: 'May–Oct',        unit: 'check conditions',  text: true },
];

const IMG_PLACEHOLDER: React.CSSProperties = {
  width: '100%', background: 'var(--surface2)',
  border: '1px solid var(--border)',
  display: 'flex', flexDirection: 'column',
  alignItems: 'center', justifyContent: 'center',
  fontFamily: "'DM Mono', monospace", fontSize: 10,
  letterSpacing: '0.15em', color: 'var(--muted)',
};

const PRACTICAL_NOTES = [
  'Day passes run about $25 for adults. Worth every cent, and then some.',
  "Rentals are available in East Burke if you don't have a bike with you.",
  "Conditions are posted on the Kingdom Trails website and updated regularly. Don't ride in wet conditions — the trails are too good to ruin.",
  'Carry water. The climbing adds up faster than you expect.',
  'The Burke Mountain Hotel has a decent post-ride burger if you need one.',
];

const SIDEBAR_LINKS = [
  { label: 'Kingdom Trails Website', href: 'https://kingdomtrails.org', external: true },
  { label: 'Trail Conditions (KT)',   href: '#', external: false },
  { label: 'Burke Mountain Resort',   href: '#', external: false },
  { label: 'Full Map (this route)',   href: '#', external: false },
];

export default function ArticlePage() {
  const scrubMarkerRef = useRef<any>(null);

  useEffect(() => {
    window.scrollTo(0, 0);
    const bar = document.getElementById('reading-progress');
    if (!bar) return;
    const onScroll = () => {
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      bar.style.width = docHeight > 0 ? ((window.scrollY / docHeight) * 100) + '%' : '0%';
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  return (
    <div style={{ minHeight: '100vh' }}>
      <div
        id="reading-progress"
        role="progressbar"
        aria-label="Reading progress"
        aria-valuenow={0} aria-valuemin={0} aria-valuemax={100}
      />
      <a href="#article-body" className="skip-link">Skip to article</a>
      <Nav variant="article" />

      <main id="article-body">
        {/* ── HEADER ── */}
        <header style={{
          padding: 'clamp(80px, 10vw, 120px) clamp(16px, 5vw, 48px) 0',
          maxWidth: 1100, margin: '0 auto',
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 24, flexWrap: 'wrap' }}>
            <span style={{
              fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.25em',
              textTransform: 'uppercase', color: 'var(--forest)',
              border: '1px solid rgba(45,74,51,0.3)', padding: '5px 10px',
              background: 'rgba(45,74,51,0.06)',
            }}>
              Kingdom Trails
            </span>
            <span style={{
              fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.18em',
              textTransform: 'uppercase', padding: '5px 10px',
              background: 'var(--amber-bg)', color: 'var(--amber)',
              border: '1px solid var(--amber-border)',
            }}>
              Intermediate
            </span>
            <time
              dateTime="2025-03-12"
              style={{ fontFamily: "'DM Mono', monospace", fontSize: 10, letterSpacing: '0.15em', color: 'var(--muted)' }}
            >
              March 12, 2025
            </time>
          </div>

          <h1 style={{
            fontFamily: "'Bebas Neue', sans-serif",
            fontSize: 'clamp(40px, 6vw, 80px)',
            lineHeight: 0.95, letterSpacing: '0.02em',
            color: 'var(--bright)', marginBottom: 24,
          }}>
            First Ride at<br />Kingdom Trails
          </h1>

          <p style={{
            fontFamily: "'Cormorant Garamond', serif",
            fontSize: 'clamp(18px, 2.2vw, 22px)',
            fontWeight: 300, fontStyle: 'italic',
            color: 'var(--muted)', lineHeight: 1.5,
            maxWidth: 720, marginBottom: 40,
          }}>
            A suggested intermediate loop with moderate climbing — what to expect, where to start, and why you'll be back before the week is out.
          </p>

          {/* Stats bar */}
          <dl className="stats-bar">
            {stats.map((s, i) => (
              <div key={s.label} style={{
                padding: 'clamp(12px, 2vw, 20px) clamp(12px, 2vw, 24px)',
                borderRight: i < stats.length - 1 ? '1px solid var(--border)' : 'none',
                display: 'flex', flexDirection: 'column', gap: 4,
              }}>
                <dt style={{ fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.22em', textTransform: 'uppercase', color: 'var(--muted)' }}>
                  {s.label}
                </dt>
                <dd style={{ margin: 0 }}>
                  <span style={{
                    fontFamily: "'Bebas Neue', sans-serif",
                    fontSize: s.text ? 'clamp(18px, 2vw, 22px)' : 'clamp(20px, 2.5vw, 28px)',
                    color: 'var(--bright)', lineHeight: 1, display: 'block',
                  }}>
                    {s.value}
                  </span>
                  <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.1em', color: 'var(--muted)' }}>
                    {s.unit}
                  </span>
                </dd>
              </div>
            ))}
          </dl>
        </header>

        {/* ── MAP ── */}
        <ArticleMap onMarkerRef={m => { scrubMarkerRef.current = m; }} />

        {/* ── ELEVATION ── */}
        <ElevationChart scrubMarkerRef={scrubMarkerRef} />

        {/* ── BODY + SIDEBAR ── */}
        <div className="article-layout">

          {/* Article body */}
          <article>

            <BodySection title="Getting There" first>
              <p className="drop-cap" style={bodyP}>
                Kingdom Trails is headquartered in East Burke, Vermont — a small village about ten miles east of I-91 off Exit 23. The main trailhead and welcome center is on Route 114, hard to miss. Day passes are purchased here; the network runs on a pay-to-ride model that funds the trail maintenance, and it shows. These trails are impeccably built and cared for.
              </p>
              <p style={bodyP}>
                Parking fills up fast on summer weekends. Get there before 9am or be prepared to park along the road and walk in. Weekdays are a different universe.
              </p>
              <figure style={{ margin: '0 0 8px' }}>
                <div
                  role="img"
                  aria-label="Photo placeholder: Welcome center and trailhead, East Burke"
                  style={{ ...IMG_PLACEHOLDER, height: 'clamp(220px, 30vw, 360px)' }}
                >
                  [ Photo: Welcome center and trailhead, East Burke ]
                </div>
                <figcaption style={captionStyle}>Welcome center · East Burke, VT</figcaption>
              </figure>
            </BodySection>

            <BodySection title="The Loop">
              <p style={bodyP}>
                There's no single "right" first ride at KT, but this loop threads together what I'd consider the essential character of the network — some sustained climbing on Darling Hill Road, a long rolling ridgeline, and a descent that will make you immediately want to ride it again.
              </p>
              <SubLabel>Starting out</SubLabel>
              <p style={bodyP}>
                From the trailhead, pick up <strong>Sidewinder</strong> almost immediately — it's a flowing green that eases you into the trail surface and gives your legs a warmup without demanding much. From there, transition onto <strong>East Branch</strong>, which follows the river drainage and stays relatively flat. It's not flashy but it sets the rhythm.
              </p>
              <p style={bodyP}>
                The climbing begins in earnest once you turn onto <strong>Coronary Bypass</strong>. Don't let the name fool you — it's a steady grind up Darling Hill, and this is where intermediate fitness earns its keep. The surface is excellent, the gradient is manageable, and the canopy is thick enough to keep you cool in July.
              </p>
              <blockquote style={{
                borderLeft: '3px solid var(--forest)',
                padding: '16px 24px', margin: '32px 0',
                background: 'rgba(45,74,51,0.04)',
              }}>
                <p style={{ fontSize: 22, fontStyle: 'italic', color: 'var(--bright)', fontWeight: 300, marginBottom: 10 }}>
                  "There's a moment on the Darling Hill climb where the trees open up and you can see the whole ridge ahead of you. That's when it clicks."
                </p>
                <cite style={{ fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.2em', textTransform: 'uppercase', color: 'var(--muted)', fontStyle: 'normal' }}>
                  — personal notes, first visit
                </cite>
              </blockquote>
            </BodySection>

            <BodySection title="The Ridgeline">
              <p style={bodyP}>
                Once on the ridge, <strong>Kitchel</strong> is the reward. A long, rolling stretch of natural surface singletrack with just enough technical variation to keep you focused. Some rooty sections, a few short punchy climbs, tight switchbacks through mature hardwoods. This is Kingdom Trails doing what it does best.
              </p>
              <p style={bodyP}>
                The views through the trees toward Burke Mountain come and go. In mud season they're better — bare branches, long sight lines. In full summer leaf it's close and green and fast. Both are good.
              </p>
              <figure style={{ margin: '0 0 8px' }}>
                <div
                  role="img"
                  aria-label="Photo placeholder: Kitchel trail, looking south toward Burke Mountain"
                  style={{ ...IMG_PLACEHOLDER, height: 'clamp(180px, 22vw, 260px)' }}
                >
                  [ Photo: Kitchel trail, looking south toward Burke Mountain ]
                </div>
                <figcaption style={captionStyle}>Kitchel · looking south</figcaption>
              </figure>
              <SubLabel>The descent</SubLabel>
              <p style={bodyP}>
                The back half of the loop runs <strong>Burnham</strong> and <strong>Sidewinder</strong> back toward the valley. Burnham is where the black diamond sections live — there are two or three spots with chunky technical features you can either roll carefully or send confidently, depending on the day. Nothing mandatory if you're not feeling it.
              </p>
              <p style={bodyP}>
                Sidewinder back toward the trailhead is a long, earned, flowing descent that feels like the trail is giving something back after all that climbing. It's the kind of finish that puts a grin on your face before you've even stopped pedaling.
              </p>
            </BodySection>

            <BodySection title="Practical Notes">
              <ul role="list" style={{ listStyle: 'none', marginBottom: 28 }}>
                {PRACTICAL_NOTES.map((item, i) => (
                  <li key={i} style={{
                    fontSize: 17, lineHeight: 1.75, color: 'var(--muted)', fontWeight: 300,
                    padding: '8px 0 8px 22px',
                    borderBottom: '1px solid var(--border)',
                    position: 'relative',
                  }}>
                    <span aria-hidden="true" style={{ position: 'absolute', left: 0, color: 'var(--forest)' }}>—</span>
                    {item}
                  </li>
                ))}
              </ul>
            </BodySection>

            <BodySection title="What Comes Next">
              <p style={bodyP}>
                This loop is a starting point, not a destination. Once you know the network's character — the way climbs connect to ridgelines connect to descents — you'll start building your own routes. The trail map rewards exploration. Take a wrong turn and you usually end up somewhere interesting.
              </p>
              <p style={{ ...bodyP, marginBottom: 0 }}>
                Come back in the fall. The foliage on Darling Hill is not subtle.
              </p>
            </BodySection>

          </article>

          {/* Sidebar */}
          <aside aria-label="Article details" style={{ position: 'sticky', top: 80 }}>

            <div className="sidebar-card">
              <h2 style={sidebarHeading}>Trails on This Route</h2>
              <div style={{ padding: 18 }}>
                <ul role="list" style={{ listStyle: 'none' }}>
                  {trails.map((t, i) => (
                    <li key={t.name} style={{
                      padding: '10px 0',
                      borderBottom: i < trails.length - 1 ? '1px solid var(--border)' : 'none',
                      display: 'flex', justifyContent: 'space-between', alignItems: 'center',
                      gap: 8,
                    }}>
                      <span style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: 16, color: 'var(--bright)' }}>
                        {t.name}
                      </span>
                      <span
                        aria-label={`Difficulty: ${t.rating}`}
                        style={{
                          fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.12em',
                          textTransform: 'uppercase', padding: '3px 7px',
                          flexShrink: 0,
                          ...ratingStyles[t.ratingClass],
                        }}
                      >
                        {t.rating}
                      </span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            <div className="sidebar-card">
              <h2 style={sidebarHeading}>Conditions</h2>
              <div style={{ padding: 18 }}>
                <dl style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, margin: 0 }}>
                  {conditions.map(c => (
                    <div key={c.label}>
                      <dt style={{ fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.15em', textTransform: 'uppercase', color: 'var(--muted)', marginBottom: 2 }}>
                        {c.label}
                      </dt>
                      <dd style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: 17, color: 'var(--bright)', margin: 0 }}>
                        {c.value}
                      </dd>
                    </div>
                  ))}
                </dl>
              </div>
            </div>

            <nav aria-label="Related links" className="sidebar-card">
              <h2 style={sidebarHeading}>Useful Links</h2>
              <ul role="list" style={{ listStyle: 'none', padding: '0 18px 6px' }}>
                {SIDEBAR_LINKS.map((link, i) => (
                  <li key={link.label} style={{ borderBottom: i < SIDEBAR_LINKS.length - 1 ? '1px solid var(--border)' : 'none' }}>
                    <a
                      href={link.href}
                      className="sidebar-link"
                      target={link.external ? '_blank' : undefined}
                      rel={link.external ? 'noopener noreferrer' : undefined}
                      aria-label={link.external ? `${link.label} (opens in new tab)` : link.label}
                    >
                      {link.label}
                      <span aria-hidden="true">→</span>
                    </a>
                  </li>
                ))}
              </ul>
            </nav>

          </aside>

        </div>

        {/* ── PREV/NEXT NAV ── */}
        <nav
          aria-label="Article navigation"
          style={{ borderTop: '1px solid var(--border)', display: 'grid', gridTemplateColumns: '1fr 1fr' }}
        >
          <ArticleNavLink
            direction="Previous"
            title="Sheet Mulching on a Slope: Lessons from the NEK"
            href="/article/sheet-mulching-nek"
            align="left"
          />
          <ArticleNavLink
            direction="Next"
            title="A Season of Skiing Burke: Notes from a Local"
            href="/article/first-ride-kingdom-trails"
            align="right"
          />
        </nav>
      </main>

      <Footer />
    </div>
  );
}

const bodyP: React.CSSProperties = {
  fontSize: 19, lineHeight: 1.8,
  color: 'var(--text)', fontWeight: 300, marginBottom: 28,
};

const captionStyle: React.CSSProperties = {
  fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.15em',
  color: 'var(--muted)', marginTop: 6, marginBottom: 32, paddingLeft: 2,
  display: 'block',
};

const sidebarHeading: React.CSSProperties = {
  padding: '14px 18px 14px 15px', borderBottom: '1px solid var(--border)',
  fontFamily: "'DM Mono', monospace", fontSize: 9,
  letterSpacing: '0.25em', textTransform: 'uppercase', color: 'var(--muted)',
  background: 'var(--surface2)', margin: 0, fontWeight: 400,
};

function BodySection({ title, children, first = false }: {
  title: string; children: React.ReactNode; first?: boolean;
}) {
  return (
    <section>
      <h2 style={{
        fontFamily: "'Cormorant Garamond', serif", fontWeight: 400,
        fontSize: 'clamp(24px, 2.8vw, 32px)', color: 'var(--bright)', lineHeight: 1.2,
        margin: first ? '0 0 20px' : '48px 0 20px',
        paddingTop: first ? 0 : 48,
        borderTop: first ? 'none' : '1px solid var(--border)',
      }}>
        {title}
      </h2>
      {children}
    </section>
  );
}

function SubLabel({ children }: { children: React.ReactNode }) {
  return (
    <h3 style={{
      fontFamily: "'DM Mono', monospace", fontSize: 11,
      letterSpacing: '0.22em', textTransform: 'uppercase',
      color: 'var(--forest)', margin: '32px 0 12px',
    }}>
      {children}
    </h3>
  );
}

function ArticleNavLink({ direction, title, href, align }: {
  direction: string; title: string; href: string; align: 'left' | 'right';
}) {
  return (
    <Link
      to={href}
      className="article-nav-item"
      aria-label={`${direction}: ${title}`}
      style={{
        textAlign: align,
        borderLeft: align === 'left' ? '3px solid transparent' : 'none',
        borderRight: align === 'right' ? '3px solid transparent' : '1px solid var(--border)',
      }}
    >
      <span style={{ fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.22em', textTransform: 'uppercase', color: 'var(--muted)' }}>
        {direction}
      </span>
      <span style={{ fontFamily: "'Cormorant Garamond', serif", fontSize: 'clamp(16px, 2vw, 20px)', color: 'var(--bright)', lineHeight: 1.2 }}>
        {title}
      </span>
    </Link>
  );
}
