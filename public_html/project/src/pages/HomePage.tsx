import { Link } from 'react-router-dom';
import Nav from '../components/Nav';
import Footer from '../components/Footer';
import HeroMap from '../components/home/HeroMap';
import FeaturedMap from '../components/home/FeaturedMap';
import { articles, categoryColors } from '../data/articles';

const LABEL_STYLE: React.CSSProperties = {
  display: 'block',
  fontFamily: "'DM Mono', monospace", fontSize: 10,
  letterSpacing: '0.35em', textTransform: 'uppercase',
  color: 'var(--muted)', marginBottom: 40,
};

const SECTION_TITLE_STYLE: React.CSSProperties = {
  fontFamily: "'Cormorant Garamond', serif", fontWeight: 300,
  fontSize: 'clamp(28px, 3vw, 46px)',
  color: 'var(--bright)', lineHeight: 1.15, marginBottom: 32,
};

const CAT_ICONS: Record<string, { path: React.ReactNode; label: string }> = {
  'Kingdom Trails': {
    label: 'Globe icon representing Kingdom Trails',
    path: (
      <>
        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2z"/>
        <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
      </>
    ),
  },
  'Burke Mountain': {
    label: 'Mountain terrain icon representing Burke Mountain',
    path: <path d="M3 17l5-10 4 6 3-4 6 8"/>,
  },
  'Permaculture': {
    label: 'Plant icon representing Permaculture',
    path: <><circle cx="12" cy="8" r="4"/><path d="M12 12v10M8 22h8"/></>,
  },
  'Maps': {
    label: 'Map icon',
    path: <><path d="M9 3L3 6v15l6-3 6 3 6-3V3l-6 3-6-3z"/><path d="M9 3v15M15 6v15"/></>,
  },
  'Writing': {
    label: 'Pen icon representing Writing',
    path: <><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></>,
  },
};

const heroCategories = [
  { label: 'Kingdom Trails', href: '#places' },
  { label: 'Burke Mountain', href: '#places' },
  { label: 'Permaculture',   href: '#places' },
  { label: 'Maps',           href: '#maps'   },
  { label: 'Writing',        href: '#writing' },
];

const places = [
  {
    num: '01', location: 'East Burke, Vermont', title: 'Kingdom Trails',
    desc: 'Over 100 miles of meticulously maintained singletrack in the hills above East Burke. One of the best mountain bike trail networks in North America — and genuinely my backyard.',
    tags: ['Mountain Biking', 'Trail Running', 'Leaflet Maps', 'GeoJSON'],
  },
  {
    num: '02', location: 'Burke, Vermont', title: 'Burke Mountain',
    desc: "3,271 feet and 2,011 vertical feet of no-frills Vermont skiing. Local knowledge matters here. I've been mapping the lines, glades, and snow pockets for years.",
    tags: ['Skiing', 'Backcountry', 'Terrain Maps', 'Winter Routes'],
  },
  {
    num: '03', location: 'Burke, Vermont', title: 'The Home Place',
    desc: "A small homestead in Burke where I'm building a food forest, growing perennials, and practicing permaculture design in Zone 5. Slow work with long payoffs.",
    tags: ['Permaculture', 'Food Forest', 'Zone 5', 'Perennials'],
  },
  {
    num: '04', location: 'Smithsonian Institution · Washington, DC', title: 'Natural History Museum',
    desc: "Web developer at the Smithsonian National Museum of Natural History, working on Drupal architecture, interactive mapping, and open-source tools for one of the world's great natural history collections.",
    tags: ['Drupal', 'Twig', 'SVG / D3', 'Open Source'],
  },
];

const mapLinks = [
  'Kingdom Trails Network',
  'Burke Mountain Ski Lines',
  'NEK Touring Routes',
  'Burke Area Trails',
];

const skills = [
  'Smithsonian NMNH',
  'Drupal & Twig',
  'Leaflet & GIS / Mapping',
  'SVG & Data Visualization',
  'JavaScript / Open Source',
  'Permaculture Design',
  'Kingdom Trails regular',
];

const marqueeItems = [
  'Kingdom Trails', 'Burke Mountain', 'Leaflet Mapping',
  'Permaculture', 'Drupal', 'Northeast Kingdom',
  'Open Source', 'Smithsonian NMNH',
];

const aboutParas = [
  "I'm Sean Montague — a web developer based in Burke, Vermont, in the Northeast Kingdom. I work at the Smithsonian National Museum of Natural History, building Drupal systems, interactive maps, and data visualization tools for one of the world's great natural history institutions.",
  "This site is a personal outlet for the things I care about outside of work: riding Kingdom Trails, skiing Burke Mountain, building a food forest, and making maps of the places I know well.",
  "The technical stuff — Leaflet, GeoJSON, Twig, SVG — bleeds into the personal projects too. That's fine by me.",
];

export default function HomePage() {
  return (
    <div style={{ minHeight: '100vh' }}>
      <a href="#main-content" className="skip-link">Skip to main content</a>
      <Nav variant="home" />

      <main id="main-content">
        {/* ── HERO ── */}
        <section
          id="home"
          aria-label="Introduction"
          style={{
            minHeight: '100svh', display: 'flex', flexDirection: 'column',
            position: 'relative', overflow: 'hidden',
            borderBottom: '1px solid var(--border)',
          }}
        >
          <HeroMap />
          <div
            aria-hidden="true"
            style={{
              position: 'absolute', inset: 0, zIndex: 1,
              background: `
                linear-gradient(to bottom, rgba(247,246,242,0.55) 0%, rgba(247,246,242,0.3) 40%, rgba(247,246,242,0.88) 82%, var(--bg) 100%),
                linear-gradient(to right, rgba(247,246,242,0.7) 0%, transparent 65%)
              `,
            }}
          />

          <div style={{
            position: 'relative', zIndex: 2, flex: 1,
            display: 'flex', flexDirection: 'column', justifyContent: 'flex-end',
            padding: 'clamp(16px, 5vw, 48px)',
            paddingBottom: 'clamp(24px, 4vw, 48px)',
            maxWidth: 680,
          }}>
            <p style={{
              fontFamily: "'DM Mono', monospace", fontSize: 10,
              letterSpacing: '0.3em', textTransform: 'uppercase',
              color: 'var(--forest)', marginBottom: 16,
            }}>
              Burke, Vermont · Northeast Kingdom
            </p>
            <h1 style={{
              fontFamily: "'Bebas Neue', sans-serif",
              fontSize: 'clamp(52px, 9vw, 120px)',
              lineHeight: 0.9, letterSpacing: '0.02em', color: 'var(--bright)',
            }}>
              SEAN<br />MONTAGUE
            </h1>
            <p style={{
              marginTop: 20, fontFamily: "'Cormorant Garamond', serif",
              fontSize: 'clamp(17px, 2vw, 19px)', fontWeight: 300, fontStyle: 'italic',
              color: 'var(--text)', lineHeight: 1.5, maxWidth: 500,
            }}>
              Maps, trails, and growing things. Web developer at the Smithsonian, living and exploring in the Northeast Kingdom.
            </p>
            <div style={{ display: 'flex', gap: 16, alignItems: 'center', marginTop: 28, flexWrap: 'wrap' }}>
              <a
                href="#maps"
                style={{
                  fontFamily: "'DM Mono', monospace", fontSize: 10,
                  letterSpacing: '0.18em', textTransform: 'uppercase',
                  background: 'var(--forest)', color: '#fff',
                  padding: '13px 26px', textDecoration: 'none',
                  display: 'inline-flex', alignItems: 'center', minHeight: 44,
                }}
              >
                Explore the Maps
              </a>
              <a
                href="#writing"
                style={{
                  fontFamily: "'DM Mono', monospace", fontSize: 10,
                  letterSpacing: '0.18em', textTransform: 'uppercase',
                  color: 'var(--muted)', textDecoration: 'none',
                  borderBottom: '1px solid var(--border)', paddingBottom: 2,
                  display: 'inline-flex', alignItems: 'center', minHeight: 44,
                }}
              >
                Read the Writing
              </a>
            </div>
          </div>

          {/* Hero category bar */}
          <div className="hero-cat-bar" role="navigation" aria-label="Jump to section">
            {heroCategories.map(cat => (
              <HeroCatLink key={cat.label} cat={cat} />
            ))}
          </div>
        </section>

        {/* ── MARQUEE ── */}
        <div
          aria-hidden="true"
          style={{
            overflow: 'hidden', borderBottom: '1px solid var(--border)',
            padding: '13px 0', background: 'var(--surface)',
          }}
        >
          <div className="animate-marquee" style={{ display: 'flex', whiteSpace: 'nowrap' }}>
            {[...marqueeItems, ...marqueeItems].map((item, i) => (
              <span key={i} style={{ display: 'flex', alignItems: 'center', flexShrink: 0 }}>
                <span style={{
                  fontFamily: "'DM Mono', monospace", fontSize: 10,
                  letterSpacing: '0.22em', textTransform: 'uppercase',
                  color: 'var(--muted)', padding: '0 28px',
                }}>
                  {item}
                </span>
                <span style={{ color: 'var(--forest)', fontSize: 8, flexShrink: 0 }}>/</span>
              </span>
            ))}
          </div>
        </div>

        {/* ── FEATURED MAP ── */}
        <section
          id="maps"
          aria-labelledby="maps-heading"
          className="section-pad"
          style={{ background: 'var(--surface)', borderBottom: '1px solid var(--border)' }}
        >
          <span style={LABEL_STYLE} aria-hidden="true">Interactive Maps</span>
          <div className="grid-2col">
            <div>
              <h2 id="maps-heading" style={SECTION_TITLE_STYLE}>
                The Kingdom, <em style={{ color: 'var(--forest)', fontStyle: 'italic' }}>mapped.</em>
              </h2>
              <p style={{ fontSize: 16, lineHeight: 1.85, color: 'var(--muted)', fontWeight: 300, marginBottom: 16 }}>
                Custom Leaflet maps of the places I know best — Kingdom Trails singletrack, Burke Mountain terrain, back-road touring routes, and the land around Burke.
              </p>
              <p style={{ fontSize: 16, lineHeight: 1.85, color: 'var(--muted)', fontWeight: 300, marginBottom: 24 }}>
                Built with Leaflet.js, OpenTopoMap tiles, and GeoJSON from years of riding, skiing, and wandering. All open-source, all hand-crafted.
              </p>
              <nav aria-label="Map links" style={{ border: '1px solid var(--border)' }}>
                {mapLinks.map((link, i) => (
                  <MapLinkRow key={link} label={link} last={i === mapLinks.length - 1} />
                ))}
              </nav>
            </div>
            <FeaturedMap />
          </div>
        </section>

        {/* ── WRITING ── */}
        <section
          id="writing"
          aria-labelledby="writing-heading"
          className="section-pad"
          style={{ borderBottom: '1px solid var(--border)' }}
        >
          <span style={LABEL_STYLE} aria-hidden="true">Writing</span>
          <h2 id="writing-heading" className="sr-only">Writing</h2>
          <div className="grid-3col" role="list">
            {articles.map((article, i) => (
              <ArticleCard key={article.slug} article={article} index={i} />
            ))}
          </div>
        </section>

        {/* ── PLACES ── */}
        <section
          id="places"
          aria-labelledby="places-heading"
          className="section-pad"
          style={{ background: 'var(--surface2)', borderBottom: '1px solid var(--border)' }}
        >
          <span style={LABEL_STYLE} aria-hidden="true">Places &amp; Projects</span>
          <h2 id="places-heading" className="sr-only">Places and Projects</h2>
          <div className="grid-2col-equal">
            {places.map(place => (
              <PlaceCard key={place.num} place={place} />
            ))}
          </div>
        </section>

        {/* ── ABOUT ── */}
        <section
          id="about"
          aria-labelledby="about-heading"
          className="section-pad"
          style={{ borderBottom: '1px solid var(--border)' }}
        >
          <span style={LABEL_STYLE} aria-hidden="true">About</span>
          <div className="grid-2col" style={{ gap: 64 }}>
            <div>
              <h2 id="about-heading" style={{
                fontFamily: "'Cormorant Garamond', serif", fontWeight: 300,
                fontSize: 'clamp(24px, 2.8vw, 38px)',
                color: 'var(--bright)', lineHeight: 1.2, marginBottom: 24,
              }}>
                Builder, rider, <em style={{ fontStyle: 'italic', color: 'var(--forest)' }}>grower.</em>
              </h2>
              <ul role="list" style={{ listStyle: 'none', borderTop: '1px solid var(--border)' }}>
                {skills.map(skill => (
                  <li key={skill} style={{
                    fontFamily: "'DM Mono', monospace", fontSize: 10,
                    letterSpacing: '0.14em', textTransform: 'uppercase',
                    color: 'var(--muted)', padding: '12px 0',
                    borderBottom: '1px solid var(--border)',
                    display: 'flex', alignItems: 'center', gap: 10,
                  }}>
                    <span aria-hidden="true" style={{ color: 'var(--forest)' }}>—</span>
                    {skill}
                  </li>
                ))}
              </ul>
            </div>
            <div>
              {aboutParas.map((para, i) => (
                <p key={i} style={{
                  fontSize: 16, lineHeight: 1.9,
                  color: 'var(--muted)', fontWeight: 300, marginBottom: 18,
                }}>
                  {para}
                </p>
              ))}
            </div>
          </div>
        </section>

        {/* ── CONTACT ── */}
        <section
          aria-labelledby="contact-heading"
          className="on-dark"
          style={{ background: 'var(--bright)', textAlign: 'center', padding: 'clamp(48px, 8vw, 100px) clamp(16px, 5vw, 48px)' }}
        >
          <span aria-hidden="true" style={{
            display: 'block',
            fontFamily: "'DM Mono', monospace", fontSize: 10,
            letterSpacing: '0.35em', textTransform: 'uppercase',
            color: 'rgba(255,255,255,0.45)', marginBottom: 20,
          }}>
            Contact
          </span>
          <h2 id="contact-heading" style={{
            fontFamily: "'Bebas Neue', sans-serif",
            fontSize: 'clamp(40px, 6vw, 80px)',
            color: '#fff', letterSpacing: '0.04em', lineHeight: 1, marginBottom: 14,
          }}>
            Say hello.
          </h2>
          <p style={{
            fontFamily: "'Cormorant Garamond', serif", fontStyle: 'italic', fontSize: 19,
            color: 'rgba(255,255,255,0.42)', marginBottom: 32, fontWeight: 300,
          }}>
            Always happy to talk trails, maps, or growing things.
          </p>
          <a
            href="mailto:sean@seanmontague.com"
            style={{
              fontFamily: "'DM Mono', monospace", fontSize: 13, letterSpacing: '0.12em',
              color: 'rgba(255,255,255,0.85)', textDecoration: 'underline',
              textUnderlineOffset: 4, display: 'inline-block', minHeight: 44,
              lineHeight: '44px',
            }}
          >
            sean@seanmontague.com
          </a>
        </section>
      </main>

      <Footer />
    </div>
  );
}

function HeroCatLink({ cat }: { cat: { label: string; href: string } }) {
  const icon = CAT_ICONS[cat.label];
  return (
    <a href={cat.href} className="hero-cat-link">
      <svg
        width="14" height="14" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" strokeWidth="1.5"
        aria-hidden="true"
        focusable="false"
        style={{ flexShrink: 0, opacity: 0.6 }}
      >
        {icon?.path}
      </svg>
      {cat.label}
    </a>
  );
}

function MapLinkRow({ label, last }: { label: string; last: boolean }) {
  return (
    <a
      href="#"
      className="arrow-link"
      aria-label={`View ${label} map`}
      style={{
        padding: '13px 18px',
        borderBottom: last ? 'none' : '1px solid var(--border)',
        fontFamily: "'DM Mono', monospace", fontSize: 11, letterSpacing: '0.07em',
        minHeight: 44,
      }}
    >
      <span>{label}</span>
      <span className="arrow" aria-hidden="true" style={{ color: 'var(--muted)' }}>→</span>
    </a>
  );
}

function ArticleCard({ article, index }: { article: typeof articles[0]; index: number }) {
  const cols = 3;
  const isLastInRow = (index + 1) % cols === 0;
  const isInSecondRow = index >= cols;

  return (
    <Link
      to={`/article/${article.slug}`}
      className="article-card"
      role="listitem"
      aria-label={`Read: ${article.title}`}
      style={{
        borderRight: isLastInRow ? 'none' : '1px solid var(--border)',
        borderTop: isInSecondRow ? '1px solid var(--border)' : 'none',
      }}
    >
      <span style={{
        fontFamily: "'DM Mono', monospace", fontSize: 9,
        letterSpacing: '0.2em', textTransform: 'uppercase',
        color: categoryColors[article.categoryColor], marginBottom: 12,
      }}>
        {article.category}
      </span>
      <h3 style={{
        fontFamily: "'Cormorant Garamond', serif", fontSize: 21, fontWeight: 400,
        color: 'var(--bright)', lineHeight: 1.25, marginBottom: 10, flex: 1,
      }}>
        {article.title}
      </h3>
      <p style={{
        fontSize: 16, lineHeight: 1.8,
        color: 'var(--muted)', fontWeight: 300, marginBottom: 16,
      }}>
        {article.excerpt}
      </p>
      <span
        aria-label={`Published ${article.date}, ${article.readTime}`}
        style={{ fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.1em', color: 'var(--muted)' }}
      >
        {article.date} · {article.readTime}
      </span>
    </Link>
  );
}

function PlaceCard({ place }: { place: typeof places[0] }) {
  return (
    <article className="place-card">
      <span aria-hidden="true" style={{
        fontFamily: "'Bebas Neue', sans-serif", fontSize: 72,
        color: 'var(--surface2)', lineHeight: 1,
        position: 'absolute', top: 12, right: 20, userSelect: 'none',
        letterSpacing: '0.02em',
      }}>
        {place.num}
      </span>
      <p style={{
        fontFamily: "'DM Mono', monospace", fontSize: 10,
        letterSpacing: '0.18em', textTransform: 'uppercase',
        color: 'var(--muted)', marginBottom: 6,
      }}>
        {place.location}
      </p>
      <h3 style={{
        fontFamily: "'Cormorant Garamond', serif", fontSize: 28, fontWeight: 400,
        color: 'var(--bright)', marginBottom: 12, lineHeight: 1.1,
      }}>
        {place.title}
      </h3>
      <p style={{ fontSize: 15, lineHeight: 1.8, color: 'var(--muted)', fontWeight: 300 }}>
        {place.desc}
      </p>
      <ul role="list" style={{ display: 'flex', flexWrap: 'wrap', gap: 6, marginTop: 16, listStyle: 'none' }}>
        {place.tags.map(tag => (
          <li key={tag} style={{
            fontFamily: "'DM Mono', monospace", fontSize: 9, letterSpacing: '0.1em',
            textTransform: 'uppercase', padding: '3px 8px',
            border: '1px solid var(--border)', color: 'var(--muted)', background: 'var(--bg)',
          }}>
            {tag}
          </li>
        ))}
      </ul>
    </article>
  );
}
