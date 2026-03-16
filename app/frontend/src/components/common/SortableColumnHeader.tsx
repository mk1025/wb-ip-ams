import { Button } from "@/components/ui/button";
import { ArrowDownIcon, ArrowUpDownIcon, ArrowUpIcon } from "lucide-react";

export default function SortableColumnHeader({
  label,
  isActive,
  sortDir,
  onClick,
}: Readonly<{
  label: string;
  isActive: boolean;
  sortDir?: "asc" | "desc";
  onClick: () => void;
}>) {
  let sortIcon = <ArrowUpDownIcon className="text-muted-foreground size-3.5" />;

  if (isActive) {
    sortIcon =
      sortDir === "asc" ? (
        <ArrowUpIcon className="size-3.5" />
      ) : (
        <ArrowDownIcon className="size-3.5" />
      );
  }

  return (
    <Button variant="ghost" onClick={onClick} className="px-0!">
      {label}
      {sortIcon}
    </Button>
  );
}
