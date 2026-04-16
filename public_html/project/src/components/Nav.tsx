import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

interface NavProps {
  variant?: 'home' | 'article';
}

const NAV_ITEMS = ['Maps', 'Writing', 'Places', 'About'] as const;

export default function Nav({ variant = 'home' }: NavProps) {
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 60);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  return (
    <header
      role="banner"
      style={{
        position: 'fixed', top: 0, left: 0, right: 0, zIndex: 200,
        background: 'var(--bg)',
        borderBottom: `1px solid ${scrolled || variant === 'article' ? 'var(--border)' : 'transparent'}`,
        transition: 'border-color 0.3s',
      }}
    >
      <nav
        aria-label="Main navigation"
        style={{
          display: 'flex', alignItems: 'center', justifyContent: 'space-between',
          padding: '0 48px', gap: 16, minHeight: 60,
        }}
      >
        <Link
          to="/"
          aria-label="Sean Montague — Home"
          style={{
            fontFamily: "'DM Mono', monospace", fontSize: 12, letterSpacing: '0.08em',
            color: 'var(--bright)', textDecoration: 'none', flexShrink: 0,
            display: 'inline-flex', alignItems: 'center', minHeight: 44,
          }}
        >
          Sean Montague <span aria-hidden="true" style={{ color: 'var(--forest)', marginLeft: 4 }}>/ Burke, VT</span>
        </Link>

        {variant === 'home' ? (
          <ul
            role="list"
            style={{ display: 'flex', gap: 28, listStyle: 'none', margin: 0, padding: 0, flexWrap: 'wrap', rowGap: 0 }}
          >
            {NAV_ITEMS.map(label => (
              <li key={label}>
                <a href={`#${label.toLowerCase()}`} className="nav-link">
                  {label}
                </a>
              </li>
            ))}
          </ul>
        ) : (
          <Link
            to="/#writing"
            className="nav-link"
            aria-label="Back to all writing"
            style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}
          >
            <span aria-hidden="true">←</span> All Writing
          </Link>
        )}
      </nav>
    </header>
  );
}
