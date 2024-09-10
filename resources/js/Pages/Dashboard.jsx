import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function Dashboard({ auth }) {
    const [companies, setCompanies] = useState([]);
    const [inn, setInn] = useState('');
    const [title, setTitle] = useState('');
    const [errors, setErrors] = useState({});
    const [successMessage, setSuccessMessage] = useState(null);

    // Fetch companies on load
    useEffect(() => {
        axios.get('/api/companies')
            .then(response => {
                setCompanies(response.data);
            })
            .catch(error => {
                console.error('Error fetching companies:', error);
            });
    }, []);

    // Handle new company creation
    const handleCreateCompany = (e) => {
        e.preventDefault();
        axios.post('/api/companies', { inn, title })
            .then(response => {
                setCompanies([...companies, response.data]);
                setInn('');
                setTitle('');
                setErrors({});
                setSuccessMessage('Company created successfully!');
            })
            .catch(error => {
                if (error.response && error.response.data.errors) {
                    setErrors(error.response.data.errors); // Set errors from the response
                } else if (error.response && error.response.data.error) {
                    setErrors({ general: error.response.data.error }); // Set general error
                }
                setSuccessMessage(null); // Clear success message
            });
    };

    // Handle company deletion
    const handleDeleteCompany = (id) => {
        axios.delete(`/api/companies/${id}`)
            .then(() => {
                setCompanies(companies.filter(company => company.id !== id));
                setSuccessMessage('Company deleted successfully!');
            })
            .catch(error => {
                console.error('Error deleting company:', error);
            });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                        {successMessage && <div className="mb-4 text-green-500">{successMessage}</div>}

                        <div className="mt-6">
                            <h3 className="text-lg font-semibold mb-4">My Companies</h3>
                            <ul>
                                {companies.map(company => (
                                    <li key={company.id} className="mb-2 flex justify-between">
                                        <span>{company.title} (INN: {company.inn})</span>
                                        <button
                                            className="text-red-500"
                                            onClick={() => handleDeleteCompany(company.id)}
                                        >
                                            Delete
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="mt-6">
                            <h3 className="text-lg font-semibold mb-4">Create New Company</h3>
                            <form onSubmit={handleCreateCompany}>
                                {errors.general && <p className="text-red-500">{errors.general}</p>}

                                {errors.inn && <p className="text-red-500">{errors.inn[0]}</p>}
                                <div className="mb-4">
                                    <label className="block text-sm font-medium">INN:</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                                        value={inn}
                                        onChange={(e) => setInn(e.target.value)}
                                    />
                                </div>

                                {errors.title && <p className="text-red-500">{errors.title[0]}</p>}
                                <div className="mb-4">
                                    <label className="block text-sm font-medium">Title:</label>
                                    <input
                                        type="text"
                                        className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                                        value={title}
                                        onChange={(e) => setTitle(e.target.value)}
                                    />
                                </div>

                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-500 text-white rounded-md"
                                >
                                    Create Company
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
