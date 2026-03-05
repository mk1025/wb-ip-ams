import { useLoginMutation } from "@/hooks/mutations/auth";
import { LoginRequest } from "@/validation/auth";
import { Controller, useForm } from "react-hook-form";
import z from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
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
import { Link, useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";

type FormValues = z.infer<typeof LoginRequest>;

export default function LoginPage() {
  const { mutateAsync: login, isPending } = useLoginMutation();

  const navigate = useNavigate();

  const form = useForm<FormValues>({
    resolver: zodResolver(LoginRequest),
    defaultValues: {
      email: "",
      password: "",
    },
  });

  async function handleLogin(values: FormValues) {
    await login(values).then(() => {
      form.reset();

      navigate("/dashboard");
    });
  }

  return (
    <>
      <title>Login - IP Address Manager</title>
      <Card className="w-full max-w-lg">
        <CardHeader>
          <CardTitle>Login</CardTitle>
          <CardDescription>
            Enter your credentials to access your account
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form
            id="login-form"
            onSubmit={form.handleSubmit(handleLogin)}
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
                      autoComplete="current-password"
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
              <Button type="submit" form="login-form" disabled={isPending}>
                {isPending && <Spinner />}
                {isPending ? "Logging in..." : "Login"}
              </Button>
            </div>
            <div className="w-full justify-end md:order-first md:justify-start">
              <p className="text-muted-foreground text-right text-sm md:text-left">
                Don't have an account?{" "}
                <Link to="/register" className="text-primary hover:underline">
                  Register
                </Link>
              </p>
            </div>
          </Field>
        </CardFooter>
      </Card>
    </>
  );
}
