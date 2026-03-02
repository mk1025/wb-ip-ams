import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
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
import { Spinner } from "@/components/ui/spinner";
import { useRegisterMutation } from "@/hooks/mutations/auth";
import { RegisterRequest } from "@/validation/auth";
import { zodResolver } from "@hookform/resolvers/zod";
import { Controller, useForm } from "react-hook-form";
import { Link, useNavigate } from "react-router-dom";
import type z from "zod";

type FormValues = z.infer<typeof RegisterRequest>;

export default function RegisterPage() {
  const { mutateAsync: register, isPending } = useRegisterMutation();

  const navigate = useNavigate();

  const form = useForm<FormValues>({
    resolver: zodResolver(RegisterRequest),
    defaultValues: {
      email: "",
      password: "",
      password_confirmation: "",
    },
  });

  async function handleRegister(values: FormValues) {
    await register(values).then(() => {
      form.reset();

      navigate("/dashboard");
    });
  }

  return (
    <Card className="w-full max-w-lg">
      <CardHeader>
        <CardTitle>Register</CardTitle>
        <CardDescription>
          Create a new account to access your dashboard
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form
          id="register-form"
          onSubmit={form.handleSubmit(handleRegister)}
          className="space-y-4"
        >
          <FieldGroup>
            <Controller
              name="email"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel>Email</FieldLabel>
                  <Input
                    {...field}
                    type="email"
                    required
                    aria-invalid={fieldState.invalid}
                    placeholder="you@example.com"
                    autoComplete="email"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
            <Controller
              name="password"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel>Password</FieldLabel>
                  <Input
                    {...field}
                    type="password"
                    required
                    aria-invalid={fieldState.invalid}
                    placeholder="Your password"
                    autoComplete="new-password"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
            <Controller
              name="password_confirmation"
              control={form.control}
              render={({ field, fieldState }) => (
                <Field data-invalid={fieldState.invalid}>
                  <FieldLabel>Confirm Password</FieldLabel>
                  <Input
                    {...field}
                    type="password"
                    required
                    aria-invalid={fieldState.invalid}
                    placeholder="Confirm your password"
                    autoComplete="new-password"
                  />
                  {fieldState.invalid && (
                    <FieldError errors={[fieldState.error]} />
                  )}
                </Field>
              )}
            />
          </FieldGroup>
        </form>
      </CardContent>
      <CardFooter>
        <Field
          orientation={"horizontal"}
          className="flex w-full flex-wrap justify-between gap-2.5 md:flex-nowrap"
        >
          <div className="grid w-full grid-cols-2 justify-end gap-2.5 md:flex">
            <Button
              type="button"
              variant="outline"
              onClick={() => form.reset()}
            >
              Reset
            </Button>
            <Button type="submit" form="register-form" disabled={isPending}>
              {isPending && <Spinner />}
              {isPending ? "Registering..." : "Register"}
            </Button>
          </div>
          <div className="w-full justify-end md:order-first md:justify-start">
            <p className="text-muted-foreground text-right text-sm md:text-left">
              Already have an account?{" "}
              <Link to="/login" className="text-primary hover:underline">
                Login
              </Link>
            </p>
          </div>
        </Field>
      </CardFooter>
    </Card>
  );
}
