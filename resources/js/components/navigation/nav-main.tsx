import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const { state } = useSidebar();
    const [expandedItems, setExpandedItems] = useState<Record<string, boolean>>({});
    const [hoveredItem, setHoveredItem] = useState<string | null>(null);
    const submenuRefs = useRef<Record<string, HTMLUListElement | null>>({});
    const isCollapsed = state === 'collapsed';

    // Check if the current page URL starts with the item URL or matches exactly
    const isActiveItem = (itemUrl: string) => {
        return page.url === itemUrl || page.url.startsWith(itemUrl + '/');
    };

    // Toggle the expanded state of a menu item
    const toggleExpand = (title: string) => {
        setExpandedItems((prev) => ({
            ...prev,
            [title]: !prev[title],
        }));
    };

    // Initialize expanded state for items with active children
    useEffect(() => {
        const newExpandedState: Record<string, boolean> = {};

        items.forEach((item) => {
            if (item.children && item.children.length > 0) {
                const hasActiveChild = item.children.some((child) => isActiveItem(child.url));
                if (hasActiveChild) {
                    newExpandedState[item.title] = true;
                }
            }
        });

        setExpandedItems((prev) => ({ ...prev, ...newExpandedState }));
    }, [page.url]);

    // Handle mouse enter for collapsed sidebar
    const handleMouseEnter = (title: string) => {
        if (isCollapsed) {
            setHoveredItem(title);
        }
    };

    // Handle mouse leave for collapsed sidebar
    const handleMouseLeave = () => {
        if (isCollapsed) {
            setHoveredItem(null);
        }
    };

    // Render a menu item and its children if any
    const renderMenuItem = (item: NavItem) => {
        const hasChildren = item.children && item.children.length > 0;
        const isActive = isActiveItem(item.url);
        const isExpanded = expandedItems[item.title] || false;
        const isHovered = hoveredItem === item.title;

        return (
            <SidebarMenuItem key={item.title} onMouseEnter={() => handleMouseEnter(item.title)} onMouseLeave={handleMouseLeave} className="relative">
                {hasChildren ? (
                    <>
                        {/* In expanded mode, use normal toggle behavior */}
                        {!isCollapsed ? (
                            <>
                                <SidebarMenuButton
                                    isActive={isActive || (hasChildren && item.children?.some((child) => isActiveItem(child.url)))}
                                    onClick={() => toggleExpand(item.title)}
                                    tooltip={item.title}
                                >
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                    <span className="ml-auto">
                                        {isExpanded ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                    </span>
                                </SidebarMenuButton>

                                {/* Regular submenu with animation for expanded state */}
                                <div className={`overflow-hidden transition-all duration-300 ease-in-out ${isExpanded ? 'max-h-96' : 'max-h-0'}`}>
                                    <SidebarMenuSub
                                        ref={(el) => {
                                            submenuRefs.current[item.title] = el;
                                        }}
                                        className="py-1"
                                    >
                                        {item.children?.map((child) => (
                                            <SidebarMenuSubItem key={child.title}>
                                                <SidebarMenuSubButton asChild isActive={isActiveItem(child.url)}>
                                                    <Link href={child.url} prefetch>
                                                        <span>{child.title}</span>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                </div>
                            </>
                        ) : (
                            /* In collapsed mode, use dropdown menu */
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <SidebarMenuButton
                                        isActive={isActive || (hasChildren && item.children?.some((child) => isActiveItem(child.url)))}
                                        tooltip={item.title}
                                    >
                                        {item.icon && <item.icon />}
                                        <span className="sr-only">{item.title}</span>
                                    </SidebarMenuButton>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent side="right" align="start" className="min-w-48 p-0">
                                    <div className="border-border border-b px-3 py-2 font-medium">{item.title}</div>
                                    <div className="py-1">
                                        {item.children?.map((child) => (
                                            <Link
                                                key={child.title}
                                                href={child.url}
                                                prefetch
                                                className={`hover:bg-accent hover:text-accent-foreground block px-3 py-2 text-sm transition-colors ${
                                                    isActiveItem(child.url) ? 'bg-accent/50 font-medium' : ''
                                                }`}
                                            >
                                                {child.title}
                                            </Link>
                                        ))}
                                    </div>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </>
                ) : (
                    <SidebarMenuButton asChild isActive={isActive} tooltip={item.title}>
                        <Link href={item.url} prefetch>
                            {item.icon && <item.icon />}
                            <span>{isCollapsed ? '' : item.title}</span>
                        </Link>
                    </SidebarMenuButton>
                )}
            </SidebarMenuItem>
        );
    };

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>{items.map(renderMenuItem)}</SidebarMenu>
        </SidebarGroup>
    );
}
