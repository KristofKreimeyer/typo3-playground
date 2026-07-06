<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Service;

final class ComponentRegistry
{
    /**
     * @return list<array{
     *     key: string, label: string, description: string, template: string,
     *     defaultProps: array<string, mixed>, schema: array<string, mixed>,
     *     variants: list<array{key: string, label: string, props: array<string, mixed>}>
     * }>
     */
    public function getComponents(): array
    {
        return [
            $this->getHeroComponent(),
            $this->getTeaserGridComponent(),
            $this->getQuoteComponent(),
            $this->getCtaComponent(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function findByKey(string $key): ?array
    {
        foreach ($this->getComponents() as $component) {
            if ($component['key'] === $key) {
                return $component;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function getHeroComponent(): array
    {
        $defaultProps = [
            'eyebrow' => 'Digital experiences',
            'headline' => 'Ideas built for meaningful impact',
            'text' => 'We bring strategy, design, and technology together for ambitious organizations.',
            'image' => '/assets/hero-studio.jpg',
            'imageAlt' => 'A creative team working together in a studio',
            'buttonLabel' => 'Explore our work',
            'buttonUrl' => '/work',
        ];

        return [
            'key' => 'hero',
            'label' => 'Hero',
            'description' => 'Large visual entry component with headline, text, image and CTA.',
            'template' => 'Hero',
            'schema' => [
                'type' => 'object',
                'required' => ['headline'],
                'properties' => [
                    'headline' => ['type' => 'string'],
                    'text' => ['type' => 'string'],
                    'image' => ['type' => 'string'],
                    'imageAlt' => ['type' => 'string'],
                    'buttonLabel' => ['type' => 'string'],
                    'buttonUrl' => ['type' => 'string'],
                ],
            ],
            'defaultProps' => $defaultProps,
            'variants' => [
                $this->variant('default', 'Default', $defaultProps),
                $this->variant('longText', 'Long text', [
                    'eyebrow' => 'Strategy, design, and technology',
                    'headline' => 'Building connected experiences for organizations navigating meaningful change',
                    'text' => 'From early research through continuous improvement, our multidisciplinary teams work alongside yours to turn complex organizational challenges into clear, useful, and resilient digital services.',
                    'image' => '/assets/hero-workshop.jpg',
                    'imageAlt' => 'Workshop participants discussing a service journey',
                    'buttonLabel' => 'Start a conversation about your next challenge',
                    'buttonUrl' => '/contact',
                ]),
                $this->variant('withoutImage', 'Without image', [
                    'eyebrow' => 'Independent digital agency',
                    'headline' => 'Clarity for complex digital work',
                    'text' => 'Practical solutions shaped around people and purpose.',
                    'image' => '',
                    'imageAlt' => '',
                    'buttonLabel' => 'What we do',
                    'buttonUrl' => '/services',
                ]),
                $this->variant('withoutButton', 'Without button', [
                    'eyebrow' => 'Our point of view',
                    'headline' => 'Good digital work starts with shared understanding',
                    'text' => 'A focused introduction without a primary action.',
                    'image' => '/assets/hero-conversation.jpg',
                    'imageAlt' => 'Two colleagues discussing a project',
                    'buttonLabel' => '',
                    'buttonUrl' => '',
                ]),
                $this->variant('shortContent', 'Short content', [
                    'eyebrow' => '',
                    'headline' => 'Make it useful.',
                    'text' => '',
                    'image' => '',
                    'imageAlt' => '',
                    'buttonLabel' => 'Begin',
                    'buttonUrl' => '/contact',
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function getTeaserGridComponent(): array
    {
        $defaultItems = [
            ['title' => 'A clearer public service', 'text' => 'A simpler journey designed around real user needs.', 'image' => '/assets/work-service.jpg', 'imageAlt' => 'Service team reviewing a journey map', 'url' => '/work/service'],
            ['title' => 'A platform built to evolve', 'text' => 'A maintainable foundation for products and teams.', 'image' => '/assets/work-platform.jpg', 'imageAlt' => 'Abstract digital interface', 'url' => '/work/platform'],
            ['title' => 'One voice across every channel', 'text' => 'A flexible brand system for a growing organization.', 'image' => '/assets/work-brand.jpg', 'imageAlt' => 'Printed brand materials', 'url' => '/work/brand'],
        ];
        $defaultProps = ['headline' => 'Selected work', 'items' => $defaultItems];

        return [
            'key' => 'teaserGrid',
            'label' => 'Teaser Grid',
            'description' => 'Flexible collection of linked editorial or service teasers.',
            'template' => 'TeaserGrid',
            'schema' => [
                'type' => 'object',
                'required' => ['items'],
                'properties' => [
                    'headline' => ['type' => 'string'],
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['title'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'text' => ['type' => 'string'],
                                'image' => ['type' => 'string'],
                                'imageAlt' => ['type' => 'string'],
                                'url' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'defaultProps' => $defaultProps,
            'variants' => [
                $this->variant('default', 'Default', $defaultProps),
                $this->variant('oneItem', 'One item', ['headline' => 'Featured project', 'items' => [$defaultItems[0]]]),
                $this->variant('twoItems', 'Two items', ['headline' => 'Latest insights', 'items' => [$defaultItems[0], $defaultItems[1]]]),
                $this->variant('longTexts', 'Long texts', [
                    'headline' => 'Partnerships that turn complex organizational ambitions into durable digital outcomes',
                    'items' => [
                        ['title' => 'Transforming a fragmented service into one coherent customer journey across channels', 'text' => 'Research, prototyping, content design, and engineering came together to make a complicated process easier to understand and complete.', 'image' => '/assets/work-journey.jpg', 'imageAlt' => 'Customer journey workshop', 'url' => '/work/journey'],
                        ['title' => 'Creating a resilient design system for distributed teams working at scale', 'text' => 'Shared principles and production-ready patterns now help multiple teams deliver consistent experiences with less duplicated effort.', 'image' => '/assets/work-system.jpg', 'imageAlt' => 'A collection of interface components', 'url' => '/work/system'],
                    ],
                ]),
                $this->variant('missingImages', 'Missing images', [
                    'headline' => 'Our capabilities',
                    'items' => [
                        ['title' => 'Strategy and research', 'text' => 'Find the useful problem to solve.', 'image' => '', 'imageAlt' => '', 'url' => '/services/strategy'],
                        ['title' => 'Design and content', 'text' => 'Make complex things clear.', 'image' => '', 'imageAlt' => '', 'url' => '/services/design'],
                        ['title' => 'Engineering and delivery', 'text' => 'Build for change from day one.', 'image' => '', 'imageAlt' => '', 'url' => '/services/engineering'],
                    ],
                ]),
                $this->variant('emptyItems', 'Empty items', ['headline' => 'Selected work', 'items' => []]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function getQuoteComponent(): array
    {
        $defaultProps = [
            'quote' => 'The team gave us the confidence to simplify a challenge that had grown increasingly complex.',
            'author' => 'Alex Morgan',
            'role' => 'Director of Digital, North & Co.',
        ];

        return [
            'key' => 'quote',
            'label' => 'Quote',
            'description' => 'Editorial quotation with attribution and optional role.',
            'template' => 'Quote',
            'schema' => [
                'type' => 'object',
                'required' => ['quote'],
                'properties' => [
                    'quote' => ['type' => 'string'],
                    'author' => ['type' => 'string'],
                    'role' => ['type' => 'string'],
                ],
            ],
            'defaultProps' => $defaultProps,
            'variants' => [
                $this->variant('default', 'Default', $defaultProps),
                $this->variant('longQuote', 'Long quote', [
                    'quote' => 'They listened carefully, challenged our assumptions constructively, and created a shared direction that every team could understand and use long after the initial project was complete.',
                    'author' => 'Sam Rivera',
                    'role' => 'Head of Transformation, Civic Works',
                ]),
                $this->variant('withoutAuthor', 'Without author', [
                    'quote' => 'Better services begin with a clearer understanding of the people who use them.',
                    'author' => '',
                    'role' => '',
                ]),
                $this->variant('shortQuote', 'Short quote', [
                    'quote' => 'Clear thinking, beautifully delivered.',
                    'author' => 'Jamie Lee',
                    'role' => '',
                ]),
                $this->variant('longAuthorRole', 'Long author and role', [
                    'quote' => 'The result gave our teams a practical foundation for what comes next.',
                    'author' => 'Dr. Morgan Alexander-Williams',
                    'role' => 'Executive Director of Digital Transformation and Customer Experience, Regional Partnership Network',
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function getCtaComponent(): array
    {
        $defaultProps = [
            'headline' => 'Have a challenge in mind?',
            'text' => 'Tell us where you want to go. We will help define the next practical step.',
            'buttonLabel' => 'Talk to our team',
            'buttonUrl' => '/contact',
        ];

        return [
            'key' => 'cta',
            'label' => 'CTA',
            'description' => 'Focused call to action for the end of a page or section.',
            'template' => 'Cta',
            'schema' => [
                'type' => 'object',
                'required' => ['headline'],
                'properties' => [
                    'headline' => ['type' => 'string'],
                    'text' => ['type' => 'string'],
                    'buttonLabel' => ['type' => 'string'],
                    'buttonUrl' => ['type' => 'string'],
                ],
            ],
            'defaultProps' => $defaultProps,
            'variants' => [
                $this->variant('default', 'Default', $defaultProps),
                $this->variant('longText', 'Long text', [
                    'headline' => 'Ready to align your teams around a clearer digital direction?',
                    'text' => 'Bring us the challenge, constraints, and context. We will help you identify the most valuable next step and create a practical plan for moving forward together.',
                    'buttonLabel' => 'Arrange an introductory conversation',
                    'buttonUrl' => '/contact',
                ]),
                $this->variant('withoutButton', 'Without button', [
                    'headline' => 'Good work starts with a useful question.',
                    'text' => 'A closing statement without a linked action.',
                    'buttonLabel' => '',
                    'buttonUrl' => '',
                ]),
                $this->variant('shortContent', 'Short content', [
                    'headline' => 'Let’s make progress.',
                    'text' => '',
                    'buttonLabel' => 'Get in touch',
                    'buttonUrl' => '/contact',
                ]),
                $this->variant('longButtonLabel', 'Long button label', [
                    'headline' => 'Start with a focused conversation',
                    'text' => 'Share what you are working through with our team.',
                    'buttonLabel' => 'Speak with our strategy and delivery team about your project',
                    'buttonUrl' => '/contact',
                ]),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $props
     * @return array{key: string, label: string, props: array<string, mixed>}
     */
    private function variant(string $key, string $label, array $props): array
    {
        return ['key' => $key, 'label' => $label, 'props' => $props];
    }
}
