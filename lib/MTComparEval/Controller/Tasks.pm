package MTComparEval::Controller::Tasks;
use Moose;
use namespace::autoclean;

BEGIN {extends 'Catalyst::Controller'; }
extends 'Catalyst::Controller::FormBuilder';

=head1 NAME

MTComparEval::Controller::Tasks - Catalyst Controller

=head1 DESCRIPTION

Catalyst Controller.

=head1 METHODS

=cut


=head2 index

=cut

sub index :Path :Args(1) {
    my ( $self, $c, $experimentId ) = @_;

    my $experiment = $c->model( 'TestDatabase::experiments' )->find( { id => $experimentId } );
    if( !$experiment ) {
        $c->flash->{ error } = 'Experiment ' . $experimentId . ' not found.';
        $c->response->redirect( $c->uri_for_action( 'experiments/index' ) );
        $c->detach();
    } else {
        $c->stash->{ tasks } = $c->model( 'TestDatabase::tasks' )->search( { experiment_id => $experimentId } );
        $c->stash->{ experiment } = $experiment;
    }
}


=head2 edit

=cut

sub edit :Local Form( '/tasks/edit' ) {
    my ( $self, $c, $experimentId, $id ) = @_;
    my $form = $self->formbuilder;
    my $experiment = $c->model( 'TestDatabase::experiments' )->find( { id => $experimentId } );
    my $task = $c->model( 'TestDatabase::tasks' )->find_or_new( { id => $id } );

    if( $form->submitted && $form->validate ) {
        $task->name( $form->field( 'name' ) );
        $task->comment( $form->field( 'comment' ) );
        $task->experiment_id( $experimentId );
        $task->update_or_insert;

        if( !$id ) {
            $c->flash->{ message } = 'Task "' . $form->field( 'name' ) . '" was created';
        } else {
            $c->flash->{ message } = 'Task "' . $form->field( 'name' ) . '" was updated';
        }
       $c->response->redirect( $c->uri_for_action( '/tasks/index', ( $experimentId ) ) );
    } else {
        if( !$id ) {
            $c->stash->{ action } = 'Adding new task';
        } else {
            $c->stash->{ action } = 'Edit task ' . $task->name;
        }

        $form->field( name => 'name', value => $task->name );
        $form->field( name => 'comment', value => $task->comment );
    }
}



=head1 AUTHOR

A clever guy

=head1 LICENSE

This library is free software. You can redistribute it and/or modify
it under the same terms as Perl itself.

=cut

# __PACKAGE__->meta->make_immutable;

1;