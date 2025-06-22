import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    decks?: Deck[];
}

export interface Deck {
    deck_name: string;
    deck_id: number;
    href: string;
};

export interface Card {
    bigImage: string;
    flavour_text: string;
    image: string;
    oracle_text: string;
    name: string;
    number: number;
};

export interface PageProps {
    deck: {
      id: number;
    }
};

export type BreadcrumbItemType = BreadcrumbItem;
