import type { IpAddressResource } from "@wb-ip-ams/shared-types";
import type { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { MoreHorizontalIcon, PenIcon } from "lucide-react";
import { Link } from "react-router-dom";
import { DeleteIpDialog } from "./dialogs/DeleteIpDialog";

interface ColumnProps {
  currentUserId: number | undefined;
  isAdmin: boolean;
}

export function IpListColumns({
  currentUserId,
  isAdmin,
}: ColumnProps): ColumnDef<IpAddressResource>[] {
  return [
    {
      accessorKey: "ip_address",
      header: "IP Address",
      cell: ({ row }) => (
        <span className="font-mono">{row.getValue("ip_address")}</span>
      ),
    },
    {
      accessorKey: "label",
      header: "Label",
    },
    {
      accessorKey: "comment",
      header: "Comment",
      cell: ({ row }) => row.getValue("comment") ?? "—",
    },
    {
      accessorKey: "owner_id",
      header: "Owner",
      cell: ({ row }) => {
        const isOwner = row.original.owner_id === currentUserId;
        return (
          <span className="flex items-center gap-2">
            {row.getValue("owner_id")}
            {isOwner && (
              <span className="rounded bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700">
                You
              </span>
            )}
          </span>
        );
      },
    },
    {
      accessorKey: "created_at",
      header: "Created",
      cell: ({ row }) => (
        <span className="text-muted-foreground">
          {new Date(row.getValue("created_at")).toLocaleString()}
        </span>
      ),
    },
    {
      id: "actions",
      cell: ({ row }) => {
        const ip = row.original;
        const isOwner = ip.owner_id === currentUserId;
        const canEdit = isAdmin || isOwner;

        if (!canEdit && !isAdmin) return null;

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontalIcon className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
              <DropdownMenuSeparator />
              {canEdit && (
                <DropdownMenuItem asChild>
                  <Link to={`/ips/${ip.id}/edit`}>
                    <PenIcon className="size-4" />
                    Edit
                  </Link>
                </DropdownMenuItem>
              )}
              {isAdmin && <DeleteIpDialog ip={ip} />}
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];
}
