import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

const widthClasses = { 48: 'w-48' };

const Dropdown = ({ children }) => <DropdownMenu>{children}</DropdownMenu>;

const Trigger = ({ children }) => <DropdownMenuTrigger asChild>{children}</DropdownMenuTrigger>;

const Content = ({ align = 'right', width = '48', className = '', children }) => (
    <DropdownMenuContent align={align} className={cn(widthClasses[width], className)}>
        {children}
    </DropdownMenuContent>
);

const DropdownLink = ({ className = '', children, ...props }) => (
    <DropdownMenuItem asChild>
        <Link {...props} className={cn('cursor-pointer', className)}>
            {children}
        </Link>
    </DropdownMenuItem>
);

Dropdown.Trigger = Trigger;
Dropdown.Content = Content;
Dropdown.Link = DropdownLink;

export default Dropdown;
