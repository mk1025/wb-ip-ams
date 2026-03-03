import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { DropdownMenuItem } from "@/components/ui/dropdown-menu";
import { useDeleteIpAddressMutation } from "@/hooks/mutations/ip-address";
import type { IpAddressResource } from "@wb-ip-ams/shared-types";

export function DeleteIpDialog({ ip }: { ip: IpAddressResource }) {
  const { mutateAsync: deleteIp, isPending: isDeleting } =
    useDeleteIpAddressMutation();

  async function handleDelete() {
    await deleteIp(ip.id);
  }

  return (
    <AlertDialog>
      <AlertDialogTrigger asChild>
        <DropdownMenuItem
          className="text-destructive"
          disabled={isDeleting}
          onSelect={(e) => e.preventDefault()}
        >
          Delete
        </DropdownMenuItem>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>
            Are you sure you want to delete this IP address?
          </AlertDialogTitle>
          <AlertDialogDescription>
            This action cannot be undone. The IP address{" "}
            <strong>{ip.ip_address}</strong> will be permanently removed.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction onClick={handleDelete} disabled={isDeleting}>
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
