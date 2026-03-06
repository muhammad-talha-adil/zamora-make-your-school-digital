import {
  LayoutGrid,
  UserIcon,
  Settings,
  User,
  SwatchIcon,
  BuildingOfficeIcon,
  CogIcon,
  type LucideIcon,
} from 'lucide-vue-next';

const iconMap: Record<string, LucideIcon> = {
  LayoutGrid,
  UserIcon,
  Settings,
  User,
  SwatchIcon,
  BuildingOfficeIcon,
  CogIcon,
};

export function useIcon() {
  const getIcon = (name: string): LucideIcon | null => {
    return iconMap[name] || null;
  };

  return { getIcon };
}