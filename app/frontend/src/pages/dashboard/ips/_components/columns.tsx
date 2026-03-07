import type { IpAddressResource } from "@wb-ip-ams/shared-types";
import type { ColumnDef } from "@tanstack/react-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  ArrowUpIcon,
  ArrowDownIcon,
  MoreHorizontalIcon,
  PenIcon,
  ArrowUpDownIcon,
} from "lucide-react";
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
      enableSorting: true,
      header: ({ column }) => (
        <Button
          variant="ghost"
          onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          className="px-0!"
        >
          IP Address
          {column.getIsSorted() === "asc" ? (
            <ArrowUpIcon className="size-3.5" />
          ) : column.getIsSorted() === "desc" ? (
            <ArrowDownIcon className="size-3.5" />
          ) : (
            <ArrowUpDownIcon className="text-muted-foreground size-3.5" />
          )}
        </Button>
      ),
      cell: ({ row }) => (
        <span className="font-mono">{row.getValue("ip_address")}</span>
      ),
    },
    {
      accessorKey: "label",
      header: ({ column }) => (
        <Button
          variant="ghost"
          onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          className="px-0!"
        >
          Label
          {column.getIsSorted() === "asc" ? (
            <ArrowUpIcon className="size-3.5" />
          ) : column.getIsSorted() === "desc" ? (
            <ArrowDownIcon className="size-3.5" />
          ) : (
            <ArrowUpDownIcon className="text-muted-foreground size-3.5" />
          )}
        </Button>
      ),
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
        const ownerEmail = row.original.owner_email;
        return (
          <span className="text-muted-foreground inline-flex items-center gap-2.5 text-xs">
            <Badge variant="ghost" className="font-mono text-xs">
              #{row.getValue("owner_id")}
            </Badge>
            {ownerEmail ?? "—"}
            {isOwner && (
              <Badge
                variant="ghost"
                className="bg-blue-100 text-xs font-medium text-blue-700"
              >
                You
              </Badge>
            )}
          </span>
        );
      },
    },
    {
      accessorKey: "created_at",
      enableSorting: true,
      header: ({ column }) => (
        <Button
          variant="ghost"
          onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          className="px-0!"
        >
          Created
          {column.getIsSorted() === "asc" ? (
            <ArrowUpIcon className="size-3.5" />
          ) : column.getIsSorted() === "desc" ? (
            <ArrowDownIcon className="size-3.5" />
          ) : (
            <ArrowUpDownIcon className="text-muted-foreground size-3.5" />
          )}
        </Button>
      ),
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
