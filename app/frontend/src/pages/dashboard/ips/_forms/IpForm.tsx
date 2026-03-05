import { useNavigate, useParams } from "react-router-dom";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import z from "zod";
import {
  StoreIpAddressRequest,
  UpdateIpAddressRequest,
} from "@/validation/ip-address";
import {
  useCreateIpAddressMutation,
  useUpdateIpAddressMutation,
} from "@/hooks/mutations/ip-address";
import { useGetIpAddress } from "@/hooks/queries/ip-address";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Field,
  FieldError,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";

type CreateValues = z.infer<typeof StoreIpAddressRequest>;
type UpdateValues = z.infer<typeof UpdateIpAddressRequest>;

export default function IpForm() {
  const { id } = useParams<{ id: string }>();

  const isEdit = !!id;

  const navigate = useNavigate();

  const { data: existing, isLoading: isLoadingExisting } = useGetIpAddress(
    isEdit ? Number(id) : 0,
  );

  const { mutateAsync: createIp, isPending: isCreating } =
    useCreateIpAddressMutation();

  const { mutateAsync: updateIp, isPending: isUpdating } =
    useUpdateIpAddressMutation(Number(id));

  const isPending = isCreating || isUpdating;

  const createForm = useForm<CreateValues>({
    mode: "onChange",
    resolver: zodResolver(StoreIpAddressRequest),
    defaultValues: { ip_address: "", label: "", comment: "" },
  });

  const updateForm = useForm<UpdateValues>({
    mode: "onChange",
    resolver: zodResolver(UpdateIpAddressRequest),
    values: existing
      ? { label: existing.label, comment: existing.comment ?? "" }
      : undefined,
  });

  async function handleCreate(values: CreateValues) {
    await createIp(values).then(() => navigate("/ips"));
  }

  async function handleUpdate(values: UpdateValues) {
    await updateIp(values).then(() => navigate("/ips"));
  }

  if (isEdit && isLoadingExisting) {
    return (
      <div className="flex items-center justify-center py-20">
        <Spinner />
      </div>
    );
  }

  return (
    <>
      <title>
        {isEdit ? "Edit IP Address" : "Add IP Address"} - IP Address Manager
      </title>
      <div className="mx-auto max-w-xl space-y-6">
        <div>
          <h1 className="text-3xl font-bold">
            {isEdit ? "Edit IP Address" : "Add IP Address"}
          </h1>
          <p className="text-muted-foreground">
            {isEdit
              ? "Update the label or comment for this IP address"
              : "Register a new IP address"}
          </p>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>
              {isEdit ? "Edit Details" : "IP Address Details"}
            </CardTitle>
            <CardDescription>
              {isEdit
                ? "Only the label and comment can be changed."
                : "Enter the IP address along with a label and optional comment."}
            </CardDescription>
          </CardHeader>
          <CardContent>
            {isEdit ? (
              <form
                id="ip-update-form"
                onSubmit={updateForm.handleSubmit(handleUpdate)}
                className="space-y-4"
              >
                {existing && (
                  <Field>
                    <FieldLabel>IP Address</FieldLabel>
                    <Input value={existing.ip_address} disabled />
                  </Field>
                )}
                <FieldGroup>
                  <Controller
                    name="label"
                    control={updateForm.control}
                    render={({ field, fieldState }) => (
                      <Field data-invalid={fieldState.invalid}>
                        <FieldLabel>Label</FieldLabel>
                        <Input
                          {...field}
                          placeholder="e.g. Production Server"
                          aria-invalid={fieldState.invalid}
                        />
                        {fieldState.invalid && (
                          <FieldError errors={[fieldState.error]} />
                        )}
                      </Field>
                    )}
                  />
                  <Controller
                    name="comment"
                    control={updateForm.control}
                    render={({ field, fieldState }) => (
                      <Field data-invalid={fieldState.invalid}>
                        <FieldLabel>Comment (optional)</FieldLabel>
                        <Input
                          {...field}
                          placeholder="Any additional notes..."
                          aria-invalid={fieldState.invalid}
                        />
                        {fieldState.invalid && (
                          <FieldError errors={[fieldState.error]} />
                        )}
                      </Field>
                    )}
                  />
                </FieldGroup>
                <div className="flex gap-2">
                  <Button
                    type="submit"
                    form="ip-update-form"
                    disabled={isPending}
                  >
                    {isPending && <Spinner />}
                    Save Changes
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => navigate("/ips")}
                  >
                    Cancel
                  </Button>
                </div>
              </form>
            ) : (
              <form
                id="ip-create-form"
                onSubmit={createForm.handleSubmit(handleCreate)}
                className="space-y-4"
              >
                <FieldGroup>
                  <Controller
                    name="ip_address"
                    control={createForm.control}
                    render={({ field, fieldState }) => (
                      <Field data-invalid={fieldState.invalid}>
                        <FieldLabel>IP Address</FieldLabel>
                        <Input
                          {...field}
                          placeholder="e.g. 192.168.1.1 or 2001:db8::1"
                          aria-invalid={fieldState.invalid}
                        />
                        {fieldState.invalid && (
                          <FieldError errors={[fieldState.error]} />
                        )}
                      </Field>
                    )}
                  />
                  <Controller
                    name="label"
                    control={createForm.control}
                    render={({ field, fieldState }) => (
                      <Field data-invalid={fieldState.invalid}>
                        <FieldLabel>Label</FieldLabel>
                        <Input
                          {...field}
                          placeholder="e.g. Production Server"
                          aria-invalid={fieldState.invalid}
                        />
                        {fieldState.invalid && (
                          <FieldError errors={[fieldState.error]} />
                        )}
                      </Field>
                    )}
                  />
                  <Controller
                    name="comment"
                    control={createForm.control}
                    render={({ field, fieldState }) => (
                      <Field data-invalid={fieldState.invalid}>
                        <FieldLabel>Comment (optional)</FieldLabel>
                        <Input
                          {...field}
                          placeholder="Any additional notes..."
                          aria-invalid={fieldState.invalid}
                        />
                        {fieldState.invalid && (
                          <FieldError errors={[fieldState.error]} />
                        )}
                      </Field>
                    )}
                  />
                </FieldGroup>
                <Field orientation={"horizontal"} className="justify-end gap-2">
                  <Button
                    type="submit"
                    form="ip-create-form"
                    disabled={isPending}
                  >
                    {isPending && <Spinner />}
                    Add IP Address
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => navigate("/ips")}
                  >
                    Cancel
                  </Button>
                </Field>
              </form>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  );
}
