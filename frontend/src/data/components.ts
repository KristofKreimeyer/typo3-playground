import type { PlaygroundComponent } from '../types';

export const playgroundComponents: PlaygroundComponent[] = [
  {
    key: 'hero',
    label: 'Hero',
    description: 'Prominent page introduction with an optional image and primary action.',
    template: 'Hero',
    schema: {
      type: 'object', required: ['headline'], properties: {
        headline: { type: 'string' }, text: { type: 'string' }, image: { type: 'string' },
        imageAlt: { type: 'string' }, buttonLabel: { type: 'string' }, buttonUrl: { type: 'string' },
      },
    },
    defaultProps: {
      eyebrow: 'Digital experiences',
      headline: 'Ideas built for meaningful impact',
      text: 'We bring strategy, design, and technology together for ambitious organizations.',
      image: '/assets/hero-studio.jpg',
      button: { label: 'Explore our work', href: '/work' },
    },
    variants: [
      {
        key: 'default',
        label: 'Default',
        props: {
          eyebrow: 'Digital experiences',
          headline: 'Ideas built for meaningful impact',
          text: 'We bring strategy, design, and technology together for ambitious organizations.',
          image: '/assets/hero-studio.jpg',
          button: { label: 'Explore our work', href: '/work' },
        },
      },
      {
        key: 'long-text',
        label: 'Long text',
        props: {
          eyebrow: 'Strategy, design, and technology',
          headline: 'Building connected experiences for organizations navigating meaningful change',
          text: 'From early research to continuous improvement, our multidisciplinary teams work alongside yours to turn complex challenges into clear, useful, and resilient digital services.',
          image: '/assets/hero-workshop.jpg',
          button: { label: 'Start a conversation', href: '/contact' },
        },
      },
      {
        key: 'without-image',
        label: 'Without image',
        props: {
          eyebrow: 'Independent digital agency',
          headline: 'Clarity for complex digital work',
          text: 'Practical solutions shaped around people and purpose.',
          image: '',
          button: { label: 'What we do', href: '/services' },
        },
      },
    ],
  },
  {
    key: 'teaser-grid',
    label: 'Teaser Grid',
    description: 'A flexible collection of linked editorial or service teasers.',
    template: 'TeaserGrid',
    schema: {
      type: 'object', required: ['items'], properties: {
        headline: { type: 'string' },
        items: { type: 'array', items: { type: 'object', required: ['title'], properties: {
          title: { type: 'string' }, text: { type: 'string' }, image: { type: 'string' },
          imageAlt: { type: 'string' }, url: { type: 'string' },
        } } },
      },
    },
    defaultProps: {
      headline: 'Selected work',
      columns: 3,
      items: [
        { title: 'A clearer public service', category: 'Service design', image: '/assets/work-service.jpg', href: '/work/service' },
        { title: 'A platform built to evolve', category: 'Technology', image: '/assets/work-platform.jpg', href: '/work/platform' },
        { title: 'One voice across every channel', category: 'Brand systems', image: '/assets/work-brand.jpg', href: '/work/brand' },
      ],
    },
    variants: [
      {
        key: 'default',
        label: 'Default',
        props: {
          headline: 'Selected work',
          columns: 3,
          items: [
            { title: 'A clearer public service', category: 'Service design', image: '/assets/work-service.jpg', href: '/work/service' },
            { title: 'A platform built to evolve', category: 'Technology', image: '/assets/work-platform.jpg', href: '/work/platform' },
            { title: 'One voice across every channel', category: 'Brand systems', image: '/assets/work-brand.jpg', href: '/work/brand' },
          ],
        },
      },
      {
        key: 'long-text',
        label: 'Long text',
        props: {
          headline: 'Recent partnerships and outcomes from across our multidisciplinary studio',
          columns: 2,
          items: [
            { title: 'Transforming a fragmented service into one coherent customer journey', category: 'Experience strategy', image: '/assets/work-journey.jpg', href: '/work/journey' },
            { title: 'Creating a resilient design system for teams working at scale', category: 'Design systems', image: '/assets/work-system.jpg', href: '/work/system' },
          ],
        },
      },
      {
        key: 'without-image',
        label: 'Without images',
        props: {
          headline: 'Our capabilities',
          columns: 3,
          items: [
            { title: 'Strategy and research', category: 'Discover', image: '', href: '/services/strategy' },
            { title: 'Design and content', category: 'Create', image: '', href: '/services/design' },
            { title: 'Engineering and delivery', category: 'Build', image: '', href: '/services/engineering' },
          ],
        },
      },
    ],
  },
  {
    key: 'quote',
    label: 'Quote',
    description: 'Editorial quotation with attribution and optional organization.',
    template: 'Quote',
    schema: {
      type: 'object', required: ['quote'], properties: {
        quote: { type: 'string' }, author: { type: 'string' }, role: { type: 'string' },
      },
    },
    defaultProps: {
      quote: 'The team gave us the confidence to simplify a challenge that had grown increasingly complex.',
      author: 'Alex Morgan',
      role: 'Director of Digital',
      organization: 'North & Co.',
    },
    variants: [
      {
        key: 'default',
        label: 'Default',
        props: {
          quote: 'The team gave us the confidence to simplify a challenge that had grown increasingly complex.',
          author: 'Alex Morgan',
          role: 'Director of Digital',
          organization: 'North & Co.',
        },
      },
      {
        key: 'long-text',
        label: 'Long text',
        props: {
          quote: 'They listened carefully, challenged our assumptions constructively, and created a shared direction that our teams could understand and use long after the initial project was complete.',
          author: 'Sam Rivera',
          role: 'Head of Transformation',
          organization: 'Civic Works',
        },
      },
      {
        key: 'short-content',
        label: 'Short content',
        props: {
          quote: 'Clear thinking, beautifully delivered.',
          author: 'Jamie Lee',
          role: '',
          organization: '',
        },
      },
    ],
  },
  {
    key: 'cta',
    label: 'CTA',
    description: 'Focused call to action for the end of a page or section.',
    template: 'Cta',
    schema: {
      type: 'object', required: ['headline'], properties: {
        headline: { type: 'string' }, text: { type: 'string' },
        buttonLabel: { type: 'string' }, buttonUrl: { type: 'string' },
      },
    },
    defaultProps: {
      headline: 'Have a challenge in mind?',
      text: 'Tell us where you want to go. We will help define the next practical step.',
      button: { label: 'Talk to our team', href: '/contact' },
      theme: 'brand',
    },
    variants: [
      {
        key: 'default',
        label: 'Default',
        props: {
          headline: 'Have a challenge in mind?',
          text: 'Tell us where you want to go. We will help define the next practical step.',
          button: { label: 'Talk to our team', href: '/contact' },
          theme: 'brand',
        },
      },
      {
        key: 'short-content',
        label: 'Short content',
        props: {
          headline: 'Let’s make progress.',
          text: null,
          button: { label: 'Get in touch', href: '/contact' },
          theme: 'light',
        },
      },
      {
        key: 'missing-button-label',
        label: 'Missing button label',
        props: {
          headline: 'Ready for the next step?',
          text: 'Our team is available to discuss your goals and constraints.',
          button: { label: '', href: '/contact' },
          theme: 'brand',
        },
      },
    ],
  },
];
