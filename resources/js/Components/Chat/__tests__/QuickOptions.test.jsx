import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import QuickOptions from '../QuickOptions';

describe('QuickOptions Component', () => {
    const mockOnSelect = jest.fn();
    const mockOnSkip = jest.fn();

    beforeEach(() => {
        mockOnSelect.mockClear();
        mockOnSkip.mockClear();
    });

    test('renders numbered options correctly', () => {
        const question = 'Apa jenis proposal yang ingin Anda buat?';

        render(
            <QuickOptions
                question={question}
                onSelect={mockOnSelect}
                currentStep={1}
                totalSteps={3}
                onSkip={mockOnSkip}
            />
        );

        // Check if options are rendered
        expect(screen.getByText('Business Proposal')).toBeInTheDocument();
        expect(screen.getByText('Project Proposal')).toBeInTheDocument();
        expect(screen.getByText('Something else')).toBeInTheDocument();
    });

    test('shows progress indicator', () => {
        const question = 'Apa kompleksitas proposal?';

        render(
            <QuickOptions
                question={question}
                onSelect={mockOnSelect}
                currentStep={2}
                totalSteps={5}
                onSkip={mockOnSkip}
            />
        );

        expect(screen.getByText('2 of 5')).toBeInTheDocument();
        expect(screen.getByText('Skip')).toBeInTheDocument();
    });

    test('calls onSelect when option is clicked', () => {
        const question = 'Apa kompleksitas proposal?';

        render(
            <QuickOptions
                question={question}
                onSelect={mockOnSelect}
                currentStep={1}
                totalSteps={3}
                onSkip={mockOnSkip}
            />
        );

        const simpleOption = screen.getByText('Simple');
        fireEvent.click(simpleOption);

        expect(mockOnSelect).toHaveBeenCalledWith('Simple');
        expect(mockOnSelect).toHaveBeenCalledTimes(1);
    });

    test('calls onSkip when skip button is clicked', () => {
        const question = 'Apa kompleksitas proposal?';

        render(
            <QuickOptions
                question={question}
                onSelect={mockOnSelect}
                currentStep={1}
                totalSteps={3}
                onSkip={mockOnSkip}
            />
        );

        const skipButton = screen.getByText('Skip');
        fireEvent.click(skipButton);

        expect(mockOnSkip).toHaveBeenCalledTimes(1);
    });

    test('shows custom input when "Something else" is clicked', () => {
        const question = 'Apa kompleksitas proposal?';

        render(
            <QuickOptions
                question={question}
                onSelect={mockOnSelect}
                currentStep={1}
                totalSteps={3}
                onSkip={mockOnSkip}
            />
        );

        const somethingElseButton = screen.getByText('Something else');
        fireEvent.click(somethingElseButton);

        // Check if input field appears
        const input = screen.getByPlaceholderText('Or reply directly...');
        expect(input).toBeInTheDocument();
    });

    test('submits custom input correctly', () => {
        const question = 'Apa kompleksitas proposal?';

        render(
            <QuickOptions
                question={question}
                onSelect={mockOnSelect}
                currentStep={1}
                totalSteps={3}
                onSkip={mockOnSkip}
            />
        );

        // Click "Something else"
        const somethingElseButton = screen.getByText('Something else');
        fireEvent.click(somethingElseButton);

        // Type in custom input
        const input = screen.getByPlaceholderText('Or reply directly...');
        fireEvent.change(input, { target: { value: 'Very Complex' } });

        // Submit form
        const form = input.closest('form');
        fireEvent.submit(form);

        expect(mockOnSelect).toHaveBeenCalledWith('Very Complex');
    });

    test('detects different question types correctly', () => {
        const testCases = [
            {
                question: 'Apa tujuan proposal Anda?',
                expectedOptions: ['Meminta approval', 'Meminta budget']
            },
            {
                question: 'Siapa target audience proposal?',
                expectedOptions: ['Executive / Direksi', 'Management / Manager']
            },
            {
                question: 'Apakah sudah sesuai?',
                expectedOptions: ['Ya, sudah sesuai', 'Belum sesuai']
            }
        ];

        testCases.forEach(({ question, expectedOptions }) => {
            const { container } = render(
                <QuickOptions
                    question={question}
                    onSelect={mockOnSelect}
                    currentStep={1}
                    totalSteps={3}
                    onSkip={mockOnSkip}
                />
            );

            expectedOptions.forEach(option => {
                expect(screen.getByText(option)).toBeInTheDocument();
            });

            // Cleanup for next iteration
            container.remove();
        });
    });

    test('renders nothing for unrecognized questions', () => {
        const question = 'This is a completely random question';

        const { container } = render(
            <QuickOptions
                question={question}
                onSelect={mockOnSelect}
                currentStep={1}
                totalSteps={3}
                onSkip={mockOnSkip}
            />
        );

        // Should show custom input directly
        expect(screen.getByPlaceholderText('Or reply directly...')).toBeInTheDocument();
    });
});
