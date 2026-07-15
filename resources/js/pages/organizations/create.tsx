import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function CreateOrganization({ hasOrganizations }: { hasOrganizations: boolean }) {
    const { data, setData, post, errors, processing } = useForm({
        name: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('organizations.store'));
    };

    return (
        <div className="flex min-h-svh items-center justify-center bg-background p-6">
            <Head title="Create organization" />

            <Card className="w-full max-w-md">
                <CardHeader>
                    <CardTitle>Create an organization</CardTitle>
                    <CardDescription>
                        {hasOrganizations
                            ? 'Set up a new workspace for your team.'
                            : 'You need an organization to get started. It holds your projects, tasks and teammates.'}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Organization name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoFocus
                                placeholder="Acme Inc"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <Button className="w-full" disabled={processing}>
                            Create organization
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
